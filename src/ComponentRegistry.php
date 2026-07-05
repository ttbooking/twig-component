<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\LaravelData\Data;
use Symfony\Component\Finder\Finder;

/**
 * Auto-discovery класс-компонентов Twig.
 *
 * Имя в реестре выводится из FQCN относительно базового неймспейса компонентов приложения
 * ($namespace, напр. App\View\TwigComponents\) по конвенции:
 *  - namespace-зоны (сегменты до имени класса) — Str::lower, join через ':';
 *  - имя класса — Str::kebab.
 * Пример: <namespace>Corporate\AgencyParamMenu -> corporate:agency-param-menu.
 * Для нестандартных имён есть seam overrides().
 *
 * Базовый неймспейс и пути — специфика приложения, инжектятся из config/twig-component.php
 * (см. TwigComponentServiceProvider): пакет не знает про App\.
 *
 * Прод читает скомпилированный манифест bootstrap/cache/twig-component.php — плоский массив
 * строк без reflection/autoload (ленивый autoload только реально рендерящегося компонента);
 * локаль без манифеста сканирует вживую. Модель — package-manifest / spatie data-structure cache.
 */
class ComponentRegistry
{
    /** @var array<string, class-string>|null */
    private ?array $map = null;

    public function __construct(
        private readonly string $namespace,
        private readonly string $componentsPath,
        private readonly string $manifestPath,
    ) {}

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

        if (! is_dir($dir = \dirname($this->manifestPath))) {
            mkdir($dir, 0755, true);
        }

        // через tmp+rename: конкурентный процесс не прочитает полузаписанный манифест
        $tmp = $this->manifestPath.'.'.uniqid('', true).'.tmp';

        file_put_contents(
            $tmp,
            "<?php\n\n// Скомпилированный реестр twig-компонентов. Не редактировать вручную —\n"
            ."// пересобирается командой twig-component:cache (входит в php artisan optimize).\n\n"
            .'return '.var_export($map, true).";\n",
        );

        rename($tmp, $this->manifestPath);

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
        $segments = explode('\\', Str::after($class, $this->namespace));
        $className = array_pop($segments);

        $parts = array_map(static fn (string $segment): string => Str::lower($segment), $segments);
        $parts[] = Str::kebab($className);

        return implode(':', $parts);
    }

    /**
     * Инстанцируемые классы-компоненты в неймспейсе: виджеты (TwigComponent) и презентационные (Data).
     * Protected — seam для тестов (подмена источника классов без файловой системы).
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
            $class = $this->namespace.Str::beforeLast($relative, '.php');

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
            && ($reflection->implementsInterface(TwigComponent::class) || $reflection->isSubclassOf(Data::class));
    }
}
