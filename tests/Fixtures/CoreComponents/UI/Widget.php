<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\CoreComponents\UI;

use TTBooking\TwigComponent\TwigComponent;

/** Фикстура в зоне UI — проверяет lower('UI')='ui' (не kebab 'u-i'). Имя: ui:widget. */
class Widget implements TwigComponent
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
