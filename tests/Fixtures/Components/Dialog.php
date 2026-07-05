<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\Components;

use TTBooking\TwigComponent\TwigComponent;

/** Фикстура-виджет с именованными слотами header/footer + дефолтным (аналог ui:modal). Имя: dialog. */
class Dialog implements TwigComponent
{
    public function __construct(
        public string $id = '',
        public string $title = '',
    ) {}

    public function template(): string
    {
        return view('components/dialog')->name();
    }

    public function context(): array
    {
        return [];
    }
}
