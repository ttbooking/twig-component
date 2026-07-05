<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests;

use Twig\Environment;

/**
 * Механика слотов на фикстурах (Card — дефолтный слот, Dialog — именованные + фолбэк).
 * Рендер через голый Twig-шаблон, как на месте вызова.
 */
class SlotTest extends TestCase
{
    private function render(string $source, array $context = []): string
    {
        /** @var Environment $twig */
        $twig = app('twig');

        return $twig->createTemplate($source)->render($context);
    }

    public function test_tag_renders_body_into_default_slot(): void
    {
        $html = $this->render(
            "{% component 'card' with { title: 'T' } %}<p>тело</p>{% endcomponent %}"
        );

        $this->assertStringContainsString('card-title">T<', $html);
        $this->assertStringContainsString('<div class="card-body"><p>тело</p></div>', $html);
    }

    public function test_default_slot_body_executes_in_caller_scope(): void
    {
        $html = $this->render(
            "{% component 'card' %}Заказ #{{ order }}{% endcomponent %}",
            ['order' => 4321],
        );

        $this->assertStringContainsString('Заказ #4321', $html);
    }

    public function test_single_text_node_body_is_not_lost(): void
    {
        // регрессия: subparse отдаёт одиночный узел напрямую — тело не должно теряться
        $html = $this->render("{% component 'card' %}<p>только текст</p>{% endcomponent %}");

        $this->assertStringContainsString('<div class="card-body"><p>только текст</p></div>', $html);
    }

    public function test_named_footer_slot_renders(): void
    {
        $html = $this->render(
            "{% component 'dialog' with { id: 'c', title: 'T' } %}<p>тело</p>".
            "{% slot 'footer' %}<button>OK</button>{% endslot %}{% endcomponent %}"
        );

        $this->assertStringContainsString('id="c"', $html);
        $this->assertStringContainsString('<div class="dialog-body"><p>тело</p></div>', $html);
        $this->assertStringContainsString('<div class="dialog-footer"><button>OK</button></div>', $html);
    }

    public function test_footer_omitted_when_slot_absent(): void
    {
        $html = $this->render("{% component 'dialog' with { title: 'T' } %}<p>тело</p>{% endcomponent %}");

        $this->assertStringContainsString('<p>тело</p>', $html);
        $this->assertStringNotContainsString('dialog-footer', $html);
    }

    public function test_header_slot_falls_back_to_title(): void
    {
        $html = $this->render("{% component 'dialog' with { title: 'Заголовок' } %}x{% endcomponent %}");

        $this->assertStringContainsString('<h4 class="dialog-title">Заголовок</h4>', $html);
    }

    public function test_named_slot_overrides_fallback(): void
    {
        $html = $this->render(
            "{% component 'dialog' with { title: 'Дефолт' } %}x".
            "{% slot 'header' %}<h4>Своя</h4>{% endslot %}{% endcomponent %}"
        );

        $this->assertStringContainsString('<h4>Своя</h4>', $html);
        $this->assertStringNotContainsString('Дефолт', $html);
    }

    public function test_named_slot_body_executes_in_caller_scope(): void
    {
        $html = $this->render(
            "{% component 'dialog' with { title: 'T' } %}x".
            "{% slot 'footer' %}<span>№{{ ticket }}</span>{% endslot %}{% endcomponent %}",
            ['ticket' => 777],
        );

        $this->assertStringContainsString('№777', $html);
    }

    public function test_whitespace_around_named_slots_does_not_suppress_default_fallback(): void
    {
        // реалистичное многострочное форматирование: переводы строк вокруг {% slot %}
        // не должны становиться дефолтным слотом и перекрывать фолбэк шаблона
        $html = $this->render(
            "{% component 'dialog' with { title: 'T' } %}\n".
            "    {% slot 'footer' %}<button>OK</button>{% endslot %}\n".
            "{% endcomponent %}"
        );

        $this->assertStringContainsString('<em class="dialog-empty">пусто</em>', $html);
        $this->assertStringContainsString('<div class="dialog-footer"><button>OK</button></div>', $html);
    }

    public function test_unnamed_slot_tag_provides_default_slot(): void
    {
        // {% slot %} без имени в теле вызова — явная передача дефолтного слота (аналог <template #default>)
        $html = $this->render(
            "{% component 'card' %}{% slot %}<p>явный дефолт</p>{% endslot %}{% endcomponent %}"
        );

        $this->assertStringContainsString('<div class="card-body"><p>явный дефолт</p></div>', $html);
    }

    public function test_mixing_loose_body_and_explicit_default_slot_is_syntax_error(): void
    {
        $this->expectException(\Twig\Error\SyntaxError::class);

        $this->render(
            "{% component 'card' %}<p>тело</p>{% slot %}<p>и явный дефолт</p>{% endslot %}{% endcomponent %}"
        );
    }

    public function test_caller_variable_in_slot_is_escaped_exactly_once(): void
    {
        $html = $this->render(
            "{% component 'card' %}{{ evil }}{% endcomponent %}",
            ['evil' => '<script>alert(1)</script>'],
        );

        // экранируется в скоупе вызывающего; повторного эскейпа при вставке в шаблон компонента нет
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('&amp;lt;', $html);
    }
}
