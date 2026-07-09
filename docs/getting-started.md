# Начало работы

`ttbooking/twig-component` добавляет в Twig класс-компоненты и слоты: тег
`{% component %}`, функцию `component()`, слоты в стиле Vue 3 и auto-discovery
реестр компонентов с манифест-кэшем.

Ядро фреймворк-нейтрально — работает на голом Twig. Laravel-интеграция (DI через
контейнер, рендер через `view()`, artisan-команды) — опциональный слой.

## Установка

```bash
composer require ttbooking/twig-component
```

Требования:

- PHP ≥ 8.2
- Twig ≥ 3.21

## Standalone (голый Twig)

Собираем расширение вручную: реестр компонентов, фабрика инстанцирования и рендерер
шаблонов.

```php
use TTBooking\TwigComponent\ComponentExtension;
use TTBooking\TwigComponent\ComponentRegistry;
use TTBooking\TwigComponent\NativeComponentFactory;
use TTBooking\TwigComponent\TwigTemplateRenderer;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$registry = new ComponentRegistry(
    namespace: 'App\\View\\TwigComponents\\',
    componentsPath: __DIR__.'/src/View/TwigComponents',
    manifestPath: __DIR__.'/var/cache/twig-component.php',
);

$twig = new Environment(new FilesystemLoader(__DIR__.'/templates'));

$twig->addExtension(new ComponentExtension(
    $registry,
    new NativeComponentFactory(),        // контейнер опционален
    new TwigTemplateRenderer($twig),     // принимает Environment или fn (): Environment
));
```

`NativeComponentFactory` инстанцирует виджеты через `new` с именованными аргументами.
Если передать любой PSR-11 контейнер, параметры конструктора без дефолта автовайрятся
из него по типу:

```php
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$factory = new NativeComponentFactory($container);
```

## Первый компонент

Виджет-компонент — это класс, реализующий `TwigComponent`: пропсы в конструкторе,
имя шаблона в `template()`, данные для шаблона в `context()`.

```php
namespace App\View\TwigComponents\UI;

use TTBooking\TwigComponent\TwigComponent;

final class Alert implements TwigComponent
{
    public function __construct(
        public string $variant = 'info',
        public bool $dismissible = false,
    ) {}

    public function template(): string
    {
        return 'components/ui/alert.html.twig';
    }

    public function context(): array
    {
        return [];
    }
}
```

Шаблон `templates/components/ui/alert.html.twig` — экземпляр компонента доступен как
`this`, тело вызова — как дефолтный слот:

```twig
<div class="alert alert-{{ this.variant }}">
    {% if this.dismissible %}<button type="button" class="close">&times;</button>{% endif %}
    {% slot %}{% endslot %}
</div>
```

Вызов на странице. Имя `ui:alert` выведено из класса
`App\View\TwigComponents\UI\Alert` по конвенции:

```twig
{% component 'ui:alert' with { variant: 'warning', dismissible: true } %}
    Проверьте введённые данные перед отправкой.
{% endcomponent %}
```

## Прогрев манифеста

Реестр кэшируется в плоский манифест — в проде это убирает сканирование файловой
системы на каждый запрос:

```php
$registry->cache();       // собрать манифест (в деплое)
$registry->clearCache();  // сбросить
```

## Дальше

- [Компоненты](components.md) — виджеты, DI, `context()`.
- [Слоты](slots.md) — дефолтные и именованные слоты, правила передачи.
- [Laravel](laravel.md) — ServiceProvider, конфиг, artisan.
- [Рецепты](recipes.md) — готовые примеры: box, modal, select.
