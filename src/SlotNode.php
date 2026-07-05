<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Standalone {% slot 'name' %}fallback{% endslot %} в шаблоне компонента (см. SlotTokenParser).
 *
 * «Дырка под слот»: если вызывающий передал слот name — выводим его, иначе рендерим тело-фолбэк
 * (аналог `<slot name="name">fallback</slot>` в Vue 3). Значение слота лежит в $context['slots'],
 * куда его кладёт ComponentExtension::renderComponent().
 *
 * В роли «дать слот» (внутри {% component %}) этот узел НЕ компилируется: ComponentTokenParser
 * забирает его тело для захвата ещё на парсинге.
 */
#[YieldReady]
final class SlotNode extends Node
{
    public function __construct(string $name, Node $body, int $lineno)
    {
        parent::__construct(['body' => $body], ['name' => $name], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $name = $this->getAttribute('name');

        $compiler
            ->addDebugInfo($this)
            ->write('if (isset($context[\'slots\'][')->repr($name)->raw("])) {\n")
            ->indent()
            ->write('yield $context[\'slots\'][')->repr($name)->raw("];\n")
            ->outdent()
            ->write("} else {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("}\n");
    }
}
