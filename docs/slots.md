# Слоты

Слоты позволяют передать в компонент кусок разметки. Модель — по образцу Vue 3:
дефолтный слот (тело вызова) и именованные слоты `{% slot 'name' %}`.

В шаблоне компонента переданные слоты доступны через `slots`, а тег
`{% slot %}` объявляет «дырку» с фолбэком.

## Дефолтный слот

Тело тега `{% component %}` становится дефолтным слотом. В шаблоне компонента его
выводит `{% slot %}{% endslot %}`; содержимое между тегами — фолбэк, если слот не передан.

Шаблон компонента `components/ui/card.html.twig`:

```twig
<div class="card card-{{ this.variant }}">
    <h3 class="card-title">{{ this.title }}</h3>
    <div class="card-body">{% slot %}<em>пусто</em>{% endslot %}</div>
</div>
```

Вызов — тело падает в дефолтный слот:

```twig
{% component 'ui:card' with { title: 'Заказы', variant: 'default' } %}
    <p>Произвольный HTML тела карточки.</p>
{% endcomponent %}
```

Дефолтный слот доступен в шаблоне и как `slots.content`:

```twig
<div class="card-body">{{ slots.content }}</div>
```

## Именованные слоты

`{% slot 'name' %}…{% endslot %}` **верхнего уровня** внутри `{% component %}`
передаёт именованный слот (аналог `<template #name>`). Весь остальной контент тела —
дефолтный слот.

Шаблон `components/ui/dialog.html.twig` с фолбэками и условным футером:

```twig
<div class="dialog"{% if this.id %} id="{{ this.id }}"{% endif %}>
    <div class="dialog-header">
        {% slot 'header' %}<h4 class="dialog-title">{{ this.title }}</h4>{% endslot %}
    </div>
    <div class="dialog-body">{% slot %}<em class="dialog-empty">пусто</em>{% endslot %}</div>
    {% if slots.footer is defined %}
        <div class="dialog-footer">{% slot 'footer' %}{% endslot %}</div>
    {% endif %}
</div>
```

Вызов — дефолтный слот плюс именованный `footer`; шапка остаётся фолбэком из `title`:

```twig
{% component 'ui:dialog' with { id: 'confirm', title: 'Подтверждение' } %}
    <p>Удалить запись?</p>
    {% slot 'footer' %}
        <button class="btn btn-secondary">Отмена</button>
        <button class="btn btn-danger">Удалить</button>
    {% endslot %}
{% endcomponent %}
```

Переопределение шапки целиком через слот `header`:

```twig
{% component 'ui:dialog' with { id: 'info' } %}
    Текст сообщения.
    {% slot 'header' %}<h4 class="text-danger">Внимание</h4>{% endslot %}
{% endcomponent %}
```

## Явный дефолтный слот

Дефолтный слот можно передать и явно — `{% slot %}` без имени (аналог
`<template #default>`):

```twig
{% component 'ui:card' with { title: 'Заказы' } %}
    {% slot %}<p>Тело карточки.</p>{% endslot %}
    {% slot 'header' %}<h3>Свой заголовок</h3>{% endslot %}
{% endcomponent %}
```

Совмещать явный дефолтный слот и loose-контент рядом нельзя — это ошибка компиляции
шаблона.

## Правила (кратко)

- `{% slot 'name' %}` верхнего уровня внутри `{% component %}` — передача именованного
  слота; весь остальной контент тела — дефолтный слот `content`.
- Тело из одних пробелов/переводов строк дефолтным слотом не считается — фолбэк
  `{% slot %}` в шаблоне компонента сохраняется.
- `{% slot %}` глубже верхнего уровня (например, внутри `{% if %}`) — это не передача
  слота, а «дырка с фолбэком» в скоупе текущего шаблона. На обычной странице `slots`
  нет, и такой тег просто отрендерит свой фолбэк.
- Ключи `this` и `slots` зарезервированы: `context()`/пропс с таким именем вызовет
  ошибку, а не молчаливое перекрытие.

## Проброс слотов во вложенный компонент

Внутри шаблона компонента `slots` есть, поэтому `{% slot %}` умеет пробросить
полученный слот дальше — во вложенный компонент:

```twig
{# шаблон родителя: получил слот 'header' и прокидывает в ui:dialog #}
{% component 'ui:dialog' with { title: this.title } %}
    {% slot %}{% endslot %}
    {% if slots.header is defined %}
        {% slot 'header' %}{% endslot %}
    {% endif %}
{% endcomponent %}
```

## Как это устроено

Компонент рендерится отдельным проходом Twig, поэтому тело слота **захватывается в
строку** и внедряется в шаблон компонента, а не связывается нативным `{% embed %}`.
Следствия: слоты рендерятся сразу (eager), scoped-слотов нет.
