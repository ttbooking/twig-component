<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\CaptureNode;
use Twig\Node\Node;

/**
 * Скомпилированный узел тега {% component %} (см. ComponentTokenParser).
 *
 * Механика — «захват и вставка», а не нативный {% embed %}: компоненты рендерятся через
 * Laravel view()->render() (отдельный проход Twig), поэтому нативные блоки embed через эту
 * границу не пробросить. Тело каждого слота захватывается в строку CaptureNode — а тот
 * оборачивает тело в замыкание с &$context, поэтому слоты исполняются В СКОУПЕ ВЫЗЫВАЮЩЕГО
 * шаблона (видят переменные страницы). Готовые строки отдаются компоненту массивом slots
 * (дефолтный — `content`, именованные — по имени) — единственный явный канал наружу при
 * иначе изолированном рендере.
 */
#[YieldReady]
final class ComponentNode extends Node
{
    public function __construct(Node $name, Node $props, Node $slots, ?Node $default, int $lineno)
    {
        $nodes = ['name' => $name, 'props' => $props, 'slots' => $slots];
        if ($default !== null) {
            $nodes['default'] = $default;
        }

        parent::__construct($nodes, [], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this)->write("\$slots = [];\n");

        // дефолтный слот (loose-контент тела) — если он есть
        if ($this->hasNode('default')) {
            $compiler
                ->write("\$slots['content'] = ")
                ->subcompile(new CaptureNode($this->getNode('default'), $this->getTemplateLine()))
                ->raw("\n");
        }

        // именованные слоты; CaptureNode сам закрывает выражение ';'
        foreach ($this->getNode('slots') as $slotName => $slotBody) {
            $compiler
                ->write('$slots[')->repr($slotName)->raw('] = ')
                ->subcompile(new CaptureNode($slotBody, $this->getTemplateLine()))
                ->raw("\n");
        }

        $compiler
            ->write('yield $this->env->getExtension(')
            ->repr(ComponentExtension::class)
            ->raw(')->renderComponent(')
            ->subcompile($this->getNode('name'))
            ->raw(', ')
            ->subcompile($this->getNode('props'))
            ->raw(", \$slots);\n");
    }
}
