<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\TextNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Тег {% component 'name' with { props } %}…тело…{% endcomponent %} — компонент со слотами.
 *
 * Даёт то, чего не может функция component() (у выражения нет тела): обёртки с произвольным
 * HTML внутри. Тег и функция с именем `component` сосуществуют — это разные пространства
 * Twig ({% %} против выражения); бестелые вызовы остаются на функции, слотовые идут через
 * тег, оба в один renderComponent().
 *
 * Слоты (по образцу Vue 3): {% slot 'name' %} верхнего уровня тела → именованные слоты
 * (аналог <template #name>), весь остальной loose-контент → дефолтный слот `content`
 * (аналог <Comp>тело</Comp>). `with` опционален.
 */
final class ComponentTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $name = $this->parser->parseExpression();

        $props = $stream->nextIf(Token::NAME_TYPE, 'with')
            ? $this->parser->parseExpression()
            : new ArrayExpression([], $lineno);

        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        [$named, $default] = $this->splitSlots($body, $lineno);

        return new ComponentNode($name, $props, $named, $default, $lineno);
    }

    /**
     * Разделить тело тега: {% slot %}-дети верхнего уровня → именованные слоты (по имени),
     * остальное → узел дефолтного слота (или null, если loose-контента нет).
     *
     * Тело из одних пробелов/переводов строк (форматирование вокруг {% slot %}-тегов) не
     * считается дефолтным слотом — иначе оно перекрывало бы фолбэк {% slot %} в шаблоне
     * компонента (Vue так же игнорирует whitespace-only контент).
     *
     * @return array{0: Node, 1: Node|null}
     */
    private function splitSlots(Node $body, int $lineno): array
    {
        $named = [];
        $loose = [];

        // subparse отдаёт одиночный узел напрямую, обёртку Nodes — только для >1 инструкции;
        // нормализуем к списку детей, иначе на одиночном теле итерировались бы его внутренности
        $children = $body instanceof Nodes ? $body : [$body];

        foreach ($children as $child) {
            if ($child instanceof SlotNode) {
                $named[$child->getAttribute('name')] = $child->getNode('body');
            } else {
                $loose[] = $child;
            }
        }

        $blank = static fn (Node $node): bool => $node instanceof TextNode
            && trim($node->getAttribute('data')) === '';

        if (array_filter($loose, static fn (Node $node): bool => ! $blank($node)) === []) {
            $loose = [];
        }

        if (isset($named['content']) && $loose !== []) {
            throw new SyntaxError(
                'Дефолтный слот задан дважды: и loose-контентом тела {% component %}, и явным {% slot %} — оставьте что-то одно.',
                $lineno,
            );
        }

        return [new Nodes($named, $lineno), $loose === [] ? null : new Nodes($loose, $lineno)];
    }

    public function decideEnd(Token $token): bool
    {
        return $token->test('endcomponent');
    }

    public function getTag(): string
    {
        return 'component';
    }
}
