<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\CoreComponents\Forms;

use TTBooking\TwigComponent\TwigComponent;

/** Фикстура в зоне Forms — проверяет вывод зоны-префикса. Имя: forms:field. */
class Field implements TwigComponent
{
    public function template(): string
    {
        return 'components/card.html.twig';
    }

    public function context(): array
    {
        return [];
    }
}
