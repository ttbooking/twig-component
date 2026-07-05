<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\CoreComponents;

use TTBooking\TwigComponent\TwigComponent;

/** Core-фикстура-виджет с именованными слотами header/footer + дефолтным. Имя: dialog. */
class Dialog implements TwigComponent
{
    public function __construct(
        public string $id = '',
        public string $title = '',
    ) {}

    public function template(): string
    {
        return 'components/dialog.html.twig';
    }

    public function context(): array
    {
        return [];
    }
}
