<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\CoreComponents;

use TTBooking\TwigComponent\TwigComponent;

/** Core-фикстура-виджет с дефолтным слотом; template() — путь Twig-шаблона. Имя: card. */
class Card implements TwigComponent
{
    public function __construct(
        public string $title = '',
        public string $variant = 'default',
    ) {}

    public function template(): string
    {
        return 'components/card.html.twig';
    }

    public function context(): array
    {
        return [];
    }
}
