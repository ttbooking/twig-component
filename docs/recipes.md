# Рецепты

Готовые компоненты, показывающие типичные приёмы: обёртка со слотом, модалка с
именованными слотами и select-компонент с нетривиальным `context()`.

## Box: обёртка со слотом

Схлопывает повторяющуюся вёрстку карточки (header / body / footer) в один компонент.
Тело вызова падает в body; заголовок и наличие футера — пропсы.

```php
namespace App\View\TwigComponents\UI;

use TTBooking\TwigComponent\TwigComponent;

final class Box implements TwigComponent
{
    public function __construct(
        public string $title = '',
        public bool $collapsible = false,
        /** default|primary|info|warning|success|danger — цвет шапки */
        public string $variant = 'default',
        /** пустой footer (декоративная нижняя планка) */
        public bool $footer = false,
    ) {}

    public function template(): string
    {
        return 'components/ui/box.html.twig';
    }

    public function context(): array
    {
        return [];
    }
}
```

Шаблон `components/ui/box.html.twig`:

```twig
<div class="box box-{{ this.variant }}">
    <div class="box-header">
        {% slot 'header' %}<h3 class="box-title">{{ this.title }}</h3>{% endslot %}
        {% if this.collapsible %}
            <button type="button" class="btn-box-tool" data-widget="collapse">&minus;</button>
        {% endif %}
    </div>
    <div class="box-body">{% slot %}{% endslot %}</div>
    {% if this.footer %}<div class="box-footer"></div>{% endif %}
</div>
```

Вызов:

```twig
{% component 'ui:box' with { title: 'Описание', collapsible: true } %}
    <p>Произвольный HTML тела бокса.</p>
{% endcomponent %}
```

## Modal: именованные слоты

Тело вызова → `modal-body`. Слот `footer` выводится, только если передан. Слот
`header` переопределяет шапку целиком (фолбэк — заголовок из `title`).

```php
namespace App\View\TwigComponents\UI;

use TTBooking\TwigComponent\TwigComponent;

final class Modal implements TwigComponent
{
    public function __construct(
        public string $id = '',
        public string $title = '',
        /** '' | 'sm' | 'lg' — размер (класс modal-<size>) */
        public string $size = '',
        public bool $closable = true,
    ) {}

    public function template(): string
    {
        return 'components/ui/modal.html.twig';
    }

    public function context(): array
    {
        return [];
    }
}
```

Шаблон `components/ui/modal.html.twig`:

```twig
<div class="modal fade" id="{{ this.id }}" tabindex="-1">
    <div class="modal-dialog{% if this.size %} modal-{{ this.size }}{% endif %}">
        <div class="modal-content">
            <div class="modal-header">
                {% slot 'header' %}
                    {% if this.closable %}
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    {% endif %}
                    <h4 class="modal-title">{{ this.title }}</h4>
                {% endslot %}
            </div>
            <div class="modal-body">{% slot %}{% endslot %}</div>
            {% if slots.footer is defined %}
                <div class="modal-footer">{% slot 'footer' %}{% endslot %}</div>
            {% endif %}
        </div>
    </div>
</div>
```

Вызов:

```twig
{% component 'ui:modal' with { id: 'confirm', title: 'Подтверждение' } %}
    <p>Удалить заказ?</p>
    {% slot 'footer' %}
        <button class="btn btn-default" data-dismiss="modal">Отмена</button>
        <button class="btn btn-danger">Удалить</button>
    {% endslot %}
{% endcomponent %}
```

## Select: логика в `context()`

Собирает `<option>` из произвольного итерируемого источника. Пути до value/text
настраиваются пропсом `fields`; необязательная пустая опция — через `withEmpty`.
Показывает, как компонент нормализует пропсы в готовый для шаблона контекст.

```php
namespace App\View\TwigComponents\Form;

use TTBooking\TwigComponent\TwigComponent;

final class Select implements TwigComponent
{
    public function __construct(
        public string $name = '',
        public iterable $items = [],
        /** [путь до value, путь до text] */
        public array $fields = ['id', 'text'],
        public mixed $selected = null,
        public bool $multiple = false,
        /** пустая опция первой строкой: true → «все», строка → свой лейбл */
        public bool|string $withEmpty = false,
    ) {}

    public function template(): string
    {
        return 'components/form/select.html.twig';
    }

    public function context(): array
    {
        [$valueField, $textField] = $this->fields;

        $options = [];

        if ($this->withEmpty !== false) {
            $options[] = [
                'value' => '',
                'text'  => $this->withEmpty === true ? 'все' : $this->withEmpty,
            ];
        }

        foreach ($this->items as $key => $item) {
            // ассоциативная карта value => text или список объектов/массивов
            $options[] = is_scalar($item)
                ? ['value' => $key, 'text' => $item]
                : ['value' => $item[$valueField], 'text' => $item[$textField]];
        }

        $selected = (array) $this->selected;

        return ['options' => $options, 'selected' => $selected];
    }
}
```

Шаблон `components/form/select.html.twig`:

```twig
<select name="{{ this.name }}{{ this.multiple ? '[]' }}"{{ this.multiple ? ' multiple' }}>
    {% for option in options %}
        <option value="{{ option.value }}"
            {{ option.value in selected ? ' selected' }}>{{ option.text }}</option>
    {% endfor %}
</select>
```

Вызов — ассоциативная карта:

```twig
{% component 'form:select' with {
    name: 'status',
    items: { active: 'Активен', blocked: 'Заблокирован' },
    selected: 'active',
    withEmpty: 'все статусы',
} %}{% endcomponent %}
```

Вызов — список объектов с настраиваемыми полями:

```twig
{% component 'form:select' with {
    name: 'city_id',
    items: cities,
    fields: ['id', 'name'],
    selected: order.city_id,
} %}{% endcomponent %}
```
