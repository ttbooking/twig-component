# TTBooking Twig Component

Класс-компоненты и слоты для Twig: тег `{% component %}`, функция `component()`,
слоты `{% slot %}` в стиле Vue 3 и auto-discovery реестр компонентов с манифест-кэшем.

Ядро фреймворк-нейтрально — работает на голом Twig в любом PHP-приложении.
Laravel-интеграция (DI через контейнер, рендер через `view()`, artisan-команды) — опциональный
слой; интеграция с другим фреймворком — реализация двух небольших интерфейсов.

## Возможности

- **Компоненты как классы.** Виджеты (свой класс с логикой + DI) и — в Laravel —
  презентационные компоненты на [`spatie/laravel-data`](https://github.com/spatie/laravel-data)
  (только пропсы).
- **Тег и функция.** `{% component 'name' with {...} %}…{% endcomponent %}` и inline
  `component('name', {...})`.
- **Слоты в стиле Vue 3.** Дефолтный слот (тело тега) + именованные `{% slot 'name' %}` с
  фолбэком `<slot>` в шаблоне компонента. Тело слота исполняется в области видимости вызова.
- **Auto-discovery.** Компоненты находятся по неймспейсу/пути; имя выводится из класса по
  конвенции (`App\View\TwigComponents\UI\Box` → `ui:box`). Манифест кэшируется для прода.
- **Точки интеграции.** `ComponentFactory` (как инстанцировать компонент) и
  `TemplateRenderer` (как отрендерить шаблон) — всё остальное фреймворка не касается.

## Требования

- PHP ≥ 8.2
- Twig ≥ 3.21

Опционально (Laravel-интеграция): Laravel ^13,
[`rcrowe/twigbridge`](https://github.com/rcrowe/TwigBridge) ^0.14,
[`spatie/laravel-data`](https://github.com/spatie/laravel-data) ^4 для Data-компонентов.

## Установка

```bash
composer require ttbooking/twig-component
```

### Без фреймворка (голый Twig)

```php
use TTBooking\TwigComponent\{ComponentExtension, ComponentRegistry, NativeComponentFactory, TwigTemplateRenderer};
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
    new NativeComponentFactory($psr11Container), // контейнер опционален: без него — props-only конструкторы
    new TwigTemplateRenderer,
));
```

`NativeComponentFactory` инстанцирует виджеты через `new` с именованными аргументами;
если передан любой PSR-11 контейнер, параметры конструктора без дефолта автовайрятся из
него по типу. Прогреть манифест в деплое: `$registry->cache()`.

### Laravel

ServiceProvider подключается через package-discovery. Опубликуйте конфиг и включите
Twig-расширение в `config/twigbridge.php`:

```bash
php artisan vendor:publish --tag=twig-component-config
```

```php
// config/twigbridge.php
'extensions' => [
    'enabled' => [
        // …
        TTBooking\TwigComponent\ComponentExtension::class,
    ],
],
```

Провайдер сам биндит Laravel-реализации: виджеты собираются контейнером
(`app($class, $props)`), шаблоны рендерятся через `view()`, доступны Data-компоненты.

### Другой фреймворк

Реализуйте `ComponentFactory` (инстанцирование компонента из props средствами вашего DI)
и, при необходимости, `TemplateRenderer` (если рендер должен идти не напрямую через Twig) —
и соберите `ComponentExtension` как в примере выше.

## Конфигурация (Laravel)

`config/twig-component.php` — где искать компоненты и куда писать манифест:

```php
return [
    'namespace' => 'App\\View\\TwigComponents\\',
    'path'      => app_path('View/TwigComponents'),
    'manifest'  => base_path('bootstrap/cache/twig-component.php'),
];
```

## Быстрый старт

Виджет-компонент:

```php
namespace App\View\TwigComponents\UI;

use TTBooking\TwigComponent\TwigComponent;

class Box implements TwigComponent
{
    public function __construct(
        public string $title = '',
        public bool $collapsible = false,
    ) {}

    public function template(): string
    {
        // имя, понятное активному рендереру:
        // standalone — путь Twig-шаблона: return 'components/ui/box.html.twig';
        // Laravel — view-имя (view()->name() даёт IDE-навигацию):
        return view('components/ui/box')->name();
    }

    public function context(): array
    {
        return [];
    }
}
```

Шаблон `components/ui/box.html.twig` (`this` — экземпляр компонента, `slots` — переданные слоты):

```twig
<div class="box">
    <div class="box-header">
        {% slot 'header' %}<h3>{{ this.title }}</h3>{% endslot %}
    </div>
    <div class="box-body">{% slot %}{% endslot %}</div>
</div>
```

Использование на месте вызова:

```twig
{% component 'ui:box' with { title: 'Заказы' } %}
    <p>Содержимое тела — дефолтный слот.</p>
    {% slot 'header' %}<h3>Свой заголовок вместо фолбэка</h3>{% endslot %}
{% endcomponent %}
```

Презентационный компонент (только данные, **Laravel-интеграция**) наследует
`Spatie\LaravelData\Data`; пропсы доступны в шаблоне напрямую. Вне Laravel этот вид
недоступен — ограничение самого laravel-data; используйте виджеты.

## Манифест-кэш

Реестр компонентов кэшируется в плоский манифест. В Laravel (вшит в `optimize`):

```bash
php artisan twig-component:cache   # собрать (входит в php artisan optimize)
php artisan twig-component:clear   # удалить (входит в optimize:clear)
```

Без фреймворка — `$registry->cache()` / `$registry->clearCache()` в скрипте деплоя.

## Как устроены слоты

Компонент рендерится отдельным проходом Twig, поэтому тело слота **захватывается в строку**
и внедряется в шаблон компонента, а не связывается нативным `{% embed %}`. Следствия:
слоты рендерятся сразу (eager), scoped-слотов нет.

Правила (по образцу Vue 3):

- `{% slot 'name' %}` **верхнего уровня** внутри `{% component %}` передаёт именованный слот
  (аналог `<template #name>`); весь остальной контент тела — дефолтный слот `content`.
- Дефолтный слот можно передать и явно: `{% slot %}…{% endslot %}` без имени
  (аналог `<template #default>`). Совмещать явный дефолтный слот и loose-контент нельзя —
  ошибка компиляции шаблона.
- Тело из одних пробелов/переводов строк (форматирование вокруг `{% slot %}`) дефолтным
  слотом не считается — фолбэк `{% slot %}` в шаблоне компонента сохраняется.
- `{% slot %}` глубже верхнего уровня (например, внутри `{% if %}`) — это не передача слота,
  а «дырка с фолбэком» в скоупе текущего шаблона. В шаблоне компонента это позволяет
  пробрасывать свои слоты во вложенный компонент; на обычной странице `slots` нет, и такой
  тег просто отрендерит свой фолбэк на месте.
- Ключи `this` и `slots` в контексте рендера зарезервированы: `context()`/пропсы с такими
  именами вызовут ошибку, а не молчаливое перекрытие.

## Тесты

```bash
composer install
vendor/bin/phpunit                    # оба сьюта
vendor/bin/phpunit --testsuite Core   # ядро на голом Twig, без фреймворка
vendor/bin/phpunit --testsuite Laravel # интеграционный слой на Orchestra Testbench
```

БД не требуется.

## Лицензия

[MIT](LICENSE).
