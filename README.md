# TTBooking Twig Component

Класс-компоненты и слоты для Twig в Laravel: тег `{% component %}`, функция `component()`,
слоты `{% slot %}` в стиле Vue 3 и auto-discovery реестр компонентов с манифест-кэшем.

## Возможности

- **Компоненты как классы.** Виджеты (свой класс с логикой + DI через контейнер) и
  презентационные компоненты на [`spatie/laravel-data`](https://github.com/spatie/laravel-data)
  (только пропсы).
- **Тег и функция.** `{% component 'name' with {...} %}…{% endcomponent %}` и inline
  `component('name', {...})`.
- **Слоты в стиле Vue 3.** Дефолтный слот (тело тега) + именованные `{% slot 'name' %}` с
  фолбэком `<slot>` в шаблоне компонента. Тело слота исполняется в области видимости вызова.
- **Auto-discovery.** Компоненты находятся по неймспейсу/пути; имя выводится из класса по
  конвенции (`App\View\Components\UI\Box` → `ui:box`). Манифест кэшируется для прода.

## Требования

- PHP ≥ 8.2
- Laravel ^13
- Twig ^3 (через [`rcrowe/twigbridge`](https://github.com/rcrowe/TwigBridge))

## Установка

```bash
composer require ttbooking/twig-component
```

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

## Конфигурация

`config/twig-component.php` — где искать компоненты и куда писать манифест:

```php
return [
    'namespace' => 'App\\View\\Components\\',
    'path'      => app_path('View/Components'),
    'manifest'  => base_path('bootstrap/cache/twig-component.php'),
];
```

## Быстрый старт

Виджет-компонент:

```php
namespace App\View\Components\UI;

use TTBooking\TwigComponent\TwigComponent;

class Box implements TwigComponent
{
    public function __construct(
        public string $title = '',
        public bool $collapsible = false,
    ) {}

    public function template(): string
    {
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

Презентационный компонент (только данные) наследует `Spatie\LaravelData\Data`; пропсы
доступны в шаблоне напрямую.

## Манифест-кэш

Реестр компонентов кэшируется в плоский манифест (вшит в `optimize`):

```bash
php artisan twig-component:cache   # собрать (входит в php artisan optimize)
php artisan twig-component:clear   # удалить (входит в optimize:clear)
```

## Как устроены слоты

Компонент рендерится отдельным проходом Twig (`view()->render()`), поэтому тело слота
**захватывается в строку** и внедряется в шаблон компонента, а не связывается нативным
`{% embed %}`. Следствия: слоты рендерятся сразу (eager), scoped-слотов нет.

## Тесты

```bash
composer install
vendor/bin/phpunit
```

Тесты изолированы на Orchestra Testbench и фикстурах — БД не требуется.

## Лицензия

[MIT](LICENSE).
