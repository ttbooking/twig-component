<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Тег {% slot ['name'] %}…{% endslot %} — именованный слот (по образцу слотов Vue 3).
 *
 * Работает в двух ролях, различаемых местом парсинга:
 *  - в ШАБЛОНЕ КОМПОНЕНТА (standalone) — «дырка + фолбэк»: `SlotNode` компилируется в
 *    «вывести slots[name], иначе отрендерить своё тело» (аналог `<slot name>fallback</slot>`);
 *  - в ВЫЗОВЕ внутри {% component %} — «дать слот»: `ComponentTokenParser` перехватывает
 *    slot-детей и захватывает их тело в слот name (аналог `<template #name>`).
 *
 * Без имени — дефолтный слот `content` (аналог `<slot/>` и `<Comp>тело</Comp>`).
 */
final class SlotTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $name = $stream->test(Token::BLOCK_END_TYPE)
            ? 'content'
            : $stream->expect(Token::STRING_TYPE)->getValue();

        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new SlotNode($name, $body, $lineno);
    }

    public function decideEnd(Token $token): bool
    {
        return $token->test('endslot');
    }

    public function getTag(): string
    {
        return 'slot';
    }
}
