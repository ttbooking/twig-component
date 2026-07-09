<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use ReflectionClass;
use Spatie\LaravelData\Data;
use Symfony\Component\Finder\Finder;

/**
 * Auto-discovery класс-компонентов Twig. Фреймворк-нейтрален: только файловая система,
 * reflection и symfony/finder.
 *
 * Имя в реестре выводится из FQCN относительно базового неймспейса компонентов приложения
 * ($namespace, напр. App\View\TwigComponents\) по конвенции:
 *  - namespace-зоны (сегменты до имени класса) — lower, join через ':';
 *  - имя класса — kebab-case.
 * Пример: <namespace>Corporate\AgencyParamMenu -> corporate:agency-param-menu.
 * Для нестандартных имён есть seam overrides().
 *
 * Прод читает скомпилированный манифест — плоский массив строк без reflection/autoload
 * (ленивый autoload только реально рендерящегося компонента); локаль без манифеста
 * сканирует вживую. Модель — package-manifest / spatie data-structure cache.
 */
class ComponentRegistry
{
    /** @var array<string, class-string>|null */
    private ?array $map = null;

    private readonly string $namespace;

    public function __construct(
        string $namespace,
        private readonly string $componentsPath,
        private readonly string $manifestPath,
    ) {
        // нормализуем хвостовой '\': без него конфиг тихо давал бы пустой реестр
        // (склеенные FQCN вида App\View\TwigComponentsCard не существуют)
        $this->namespace = rtrim($namespace, '\\').'\\';
    }

    /**
     * Карта «имя → класс». Мемоизируется на процесс: сначала манифест, иначе живой скан.
     *
     * @return array<string, class-string>
     */
    public function map(): array
    {
        return $this->map ??= $this->loadManifest() ?? $this->build();
    }

    public function resolve(string $name): ?string
    {
        return $this->map()[$name] ?? null;
    }

    /**
     * Скан неймспейса + вывод имён + merge override. Дорого (reflection, autoload классов) —
     * в проде заменяется чтением манифеста.
     *
     * @return array<string, class-string>
     */
    public function build(): array
    {
        $map = [];

        foreach ($this->discoverClasses() as $class) {
            $name = $this->deriveName($class);

            // возможно на case-sensitive ФС (классы, различающиеся только регистром) —
            // молчаливый last-wins маскировал бы потерю компонента
            if (isset($map[$name])) {
                throw new \RuntimeException(sprintf(
                    'Коллизия имени twig-компонента «%s»: %s и %s выводятся в одно имя.',
                    $name, $map[$name], $class,
                ));
            }

            $map[$name] = $class;
        }

        // override перебивает конвенцию (нестандартные имена, тестовые фейки)
        return $this->overrides() + $map;
    }

    /**
     * Собрать и записать манифест (команда twig-component:cache).
     *
     * @return array<string, class-string>
     */
    public function cache(): array
    {
        $map = $this->build();

        // @mkdir + повторный is_dir: конкурентный процесс мог создать каталог между проверками
        if (! is_dir($dir = \dirname($this->manifestPath)) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException("Не удалось создать каталог манифеста: {$dir}");
        }

        // через tmp+rename: конкурентный процесс не прочитает полузаписанный манифест
        $tmp = $this->manifestPath.'.'.uniqid('', true).'.tmp';

        $written = file_put_contents(
            $tmp,
            "<?php\n\n// Скомпилированный реестр twig-компонентов. Не редактировать вручную —\n"
            ."// пересобирается командой twig-component:cache (входит в php artisan optimize).\n\n"
            .'return '.var_export($map, true).";\n",
        );

        if ($written === false || ! rename($tmp, $this->manifestPath)) {
            @unlink($tmp);

            throw new \RuntimeException("Не удалось записать манифест: {$this->manifestPath}");
        }

        return $this->map = $map;
    }

    public function clearCache(): bool
    {
        $this->map = null;

        return is_file($this->manifestPath) ? @unlink($this->manifestPath) : false;
    }

    public function isCached(): bool
    {
        return is_file($this->manifestPath);
    }

    /**
     * Прочитать скомпилированный манифест (плоский массив строк, без reflection/autoload).
     *
     * @return array<string, class-string>|null
     */
    private function loadManifest(): ?array
    {
        if (! is_file($this->manifestPath)) {
            return null;
        }

        $map = require $this->manifestPath;

        // повреждённый/чужой файл по пути манифеста → игнорируем, падаём на живой скан
        return is_array($map) ? $map : null;
    }

    /**
     * Явные override для нестандартных имён (конвенция сейчас покрывает всё — пусто).
     * Точка расширения для тестов и будущих оддболов.
     *
     * @return array<string, class-string>
     */
    protected function overrides(): array
    {
        return [];
    }

    /**
     * Имя в реестре из FQCN: зоны через lower, имя класса через kebab, join ':'.
     */
    public function deriveName(string $class): string
    {
        $relative = str_starts_with($class, $this->namespace)
            ? substr($class, strlen($this->namespace))
            : $class;

        $segments = explode('\\', $relative);
        $className = array_pop($segments);

        $parts = array_map(
            static fn (string $segment): string => mb_strtolower($segment, 'UTF-8'),
            $segments,
        );
        $parts[] = self::kebab($className);

        return implode(':', $parts);
    }

    /**
     * Kebab-case имени класса (эквивалент Illuminate\Support\Str::kebab):
     * дефис перед каждой заглавной, всё в нижний регистр. UI -> u-i (акронимы-зоны
     * идут через lower, сюда попадает только имя класса).
     */
    private static function kebab(string $value): string
    {
        return mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1-', $value), 'UTF-8');
    }

    /**
     * Инстанцируемые классы-компоненты в неймспейсе: виджеты (TwigComponent) и презентационные
     * (Data, если laravel-data установлен). Protected — seam для тестов (подмена источника
     * классов без файловой системы).
     *
     * @return list<class-string>
     */
    protected function discoverClasses(): array
    {
        if (! is_dir($this->componentsPath)) {
            return [];
        }

        $classes = [];

        foreach (Finder::create()->files()->in($this->componentsPath)->name('*.php') as $file) {
            // getRelativePathname() отдаёт разделитель ОС — нормализуем оба варианта в '\'
            $relative = str_replace(['/', '\\'], '\\', $file->getRelativePathname());
            $class = $this->namespace.substr($relative, 0, -strlen('.php'));

            if ($this->isComponent($class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    private function isComponent(string $class): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        $reflection = new ReflectionClass($class);

        return $reflection->isInstantiable()
            && ($reflection->implementsInterface(TwigComponent::class)
                || (class_exists(Data::class) && $reflection->isSubclassOf(Data::class)));
    }
}
