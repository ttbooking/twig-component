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
}
