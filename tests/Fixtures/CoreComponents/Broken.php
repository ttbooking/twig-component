<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\CoreComponents;

use RuntimeException;
use TTBooking\TwigComponent\TwigComponent;

/** Core-фикстура, падающая при сборке данных — для проверки обёртки ComponentRenderingException. Имя: broken. */
class Broken implements TwigComponent
{
    public function template(): string
    {
        return 'components/card.html.twig';
    }

    public function context(): array
    {
        throw new RuntimeException('boom from context');
    }
}
