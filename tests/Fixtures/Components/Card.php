<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\Components;

use TTBooking\TwigComponent\TwigComponent;

/** Фикстура-виджет с дефолтным слотом (аналог ui:box). Имя в реестре: card. */
class Card implements TwigComponent
{
    public function __construct(
        public string $title = '',
        public string $variant = 'default',
    ) {}

    public function template(): string
    {
        return view('components/card')->name();
    }

    public function context(): array
    {
        return [];
    }
}
