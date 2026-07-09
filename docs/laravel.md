# Laravel-интеграция

Опциональный слой поверх ядра: виджеты собираются контейнером Laravel, шаблоны
рендерятся через `view()`, доступны презентационные Data-компоненты и artisan-команды.

## Установка

```bash
composer require ttbooking/twig-component
```

Дополнительно для интеграции:

- Laravel ^13
- [`rcrowe/twigbridge`](https://github.com/rcrowe/TwigBridge) ^0.14
- [`spatie/laravel-data`](https://github.com/spatie/laravel-data) ^4 — для Data-компонентов

## Подключение

ServiceProvider регистрируется через package-discovery автоматически. Опубликуйте
конфиг и включите Twig-расширение в `config/twigbridge.php`:

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

## Конфигурация

`config/twig-component.php` — где искать компоненты и куда писать манифест:

```php
return [
    'namespace' => 'App\\View\\TwigComponents\\',
    'path'      => app_path('View/TwigComponents'),
    'manifest'  => base_path('bootstrap/cache/twig-component.php'),
];
```

## Имя шаблона через `view()->name()`

В Laravel `template()` возвращает view-имя. Приём `view('...')->name()` оставляет
литерал внутри `view()` — его видит Laravel Idea (клик-навигация класс↔шаблон):

```php
public function template(): string
{
    // литерал 'components/ui/box' виден IDE; ->name() возвращает то же имя строкой
    return view('components/ui/box')->name();
}
```

## Презентационные Data-компоненты

Если компоненту нужны только данные (без логики и DI), это класс на
`Spatie\LaravelData\Data` с методом `template()`. Пропсы доступны в шаблоне напрямую
(без `this`):

```php
namespace App\View\TwigComponents\UI;

use Spatie\LaravelData\Data;

final class Price extends Data
{
    public function __construct(
        public int $amount,
        public string $currency = 'RUB',
    ) {}

    public function template(): string
    {
        return view('components/ui/price')->name();
    }
}
```

Шаблон `components/ui/price.html.twig` — пропсы на верхнем уровне:

```twig
<span class="price">{{ amount }}&nbsp;{{ currency }}</span>
```

Этот вид доступен только в Laravel — ограничение самого laravel-data. Вне Laravel
используйте виджеты.

## Рендер из PHP (в тестах)

Компонент рендерится в строку без Twig-окружения — удобно в тестах компонентов:

```php
use TTBooking\TwigComponent\ComponentExtension;

$html = app(ComponentExtension::class)->renderComponent('ui:box', ['title' => 'Заказы']);

$this->assertStringContainsString('Заказы', $html);
```

## Манифест-кэш

Реестр компонентов кэшируется в плоский манифест. Команды вшиты в `optimize`:

```bash
php artisan twig-component:cache   # собрать (входит в php artisan optimize)
php artisan twig-component:clear   # удалить (входит в optimize:clear)
```

## Другой фреймворк

Реализуйте `ComponentFactory` (инстанцирование компонента из props средствами вашего
DI) и, при необходимости, `TemplateRenderer` (если рендер должен идти не напрямую через
Twig), затем соберите `ComponentExtension` как в [standalone-примере](getting-started.md).
