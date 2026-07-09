# Компоненты

Виджет-компонент — класс, реализующий `TTBooking\TwigComponent\TwigComponent`.
Он сам собирает данные для своего шаблона и получает зависимости через конструктор.

## Три метода интерфейса

```php
namespace App\View\TwigComponents\UI;

use TTBooking\TwigComponent\TwigComponent;

final class Badge implements TwigComponent
{
    // 1. Пропсы — параметры конструктора с дефолтами.
    public function __construct(
        public string $label = '',
        public string $variant = 'default',
    ) {}

    // 2. Имя шаблона, понятное активному рендереру.
    public function template(): string
    {
        return 'components/ui/badge.html.twig';
    }

    // 3. Данные шаблона (помимо `this` и `slots`).
    public function context(): array
    {
        return [];
    }
}
```

## Пропсы и `this`

Публичные свойства компонента доступны в шаблоне через `this`:

```twig
<span class="badge badge-{{ this.variant }}">{{ this.label }}</span>
```

Вызов передаёт пропсы через `with`:

```twig
{% component 'ui:badge' with { label: 'NEW', variant: 'success' } %}{% endcomponent %}
```

Неизвестный ключ props — ошибка, а не молчаливое игнорирование. Так опечатка
`varinat` не превратится молча в дефолтный `variant`:

```twig
{# InvalidArgumentException: Неизвестные props [varinat]; компонент принимает: [label, variant] #}
{% component 'ui:badge' with { label: 'NEW', varinat: 'success' } %}{% endcomponent %}
```

## `context()` — вычисляемые данные

Всё, что не является чистым пропом, компонент вычисляет в `context()`. Пропсы читаются
через `$this`, результат — ассоциативный массив переменных шаблона:

```php
namespace App\View\TwigComponents\UI;

use TTBooking\TwigComponent\TwigComponent;

final class Pagination implements TwigComponent
{
    public function __construct(
        public int $total = 0,
        public int $perPage = 20,
        public int $current = 1,
    ) {}

    public function template(): string
    {
        return 'components/ui/pagination.html.twig';
    }

    public function context(): array
    {
        $pages = (int) ceil($this->total / max(1, $this->perPage));

        return [
            'pages'   => $pages,
            'hasPrev' => $this->current > 1,
            'hasNext' => $this->current < $pages,
        ];
    }
}
```

В шаблоне переменные из `context()` — на верхнем уровне, пропсы — через `this`:

```twig
<nav class="pagination">
    {% if hasPrev %}<a href="?page={{ this.current - 1 }}">&laquo;</a>{% endif %}
    {% for page in 1..pages %}
        <a href="?page={{ page }}"{{ page == this.current ? ' class="active"' }}>{{ page }}</a>
    {% endfor %}
    {% if hasNext %}<a href="?page={{ this.current + 1 }}">&raquo;</a>{% endif %}
</nav>
```

## Зависимости через конструктор (DI)

Параметры конструктора без дефолта считаются зависимостями и разрешаются фабрикой
(контейнером). Пропсы-с-дефолтом и зависимости-без-дефолта живут в одном конструкторе:

```php
namespace App\View\TwigComponents\UI;

use App\Contracts\Clock;
use TTBooking\TwigComponent\TwigComponent;

final class Greeting implements TwigComponent
{
    public function __construct(
        private Clock $clock,        // зависимость — придёт из контейнера
        public string $name = '',    // проп — придёт из вызова
    ) {}

    public function template(): string
    {
        return 'components/ui/greeting.html.twig';
    }

    public function context(): array
    {
        $hour = (int) $this->clock->now()->format('H');

        return ['part' => $hour < 12 ? 'утро' : 'день'];
    }
}
```

## Функция `component()` — вызов без тела

Для бестелых компонентов (без слотов) есть inline-функция — удобно внутри выражений:

```twig
{{ component('ui:badge', { label: 'BETA', variant: 'info' }) }}
```

## Рендер напрямую из PHP

Компонент можно отрендерить в строку без Twig-шаблона на месте вызова — удобно в
тестах компонентов приложения:

```php
use TTBooking\TwigComponent\ComponentExtension;

/** @var ComponentExtension $extension */
$html = $extension->renderComponent('ui:badge', ['label' => 'NEW', 'variant' => 'success']);
```

## Конвенция имён

Имя компонента выводится из класса относительно зарегистрированного неймспейса.
Сегменты пути — через `:`, имена — в kebab-case, регистр приводится к нижнему:

```text
App\View\TwigComponents\UI\Box          ->  ui:box
App\View\TwigComponents\Form\Select      ->  form:select
App\View\TwigComponents\Admin\Orders\Row  ->  admin:orders:row
```
