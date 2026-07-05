<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\Components;

use RuntimeException;
use TTBooking\TwigComponent\TwigComponent;

/** Фикстура, падающая при сборке данных — для проверки обёртки ComponentRenderingException. Имя: broken. */
class Broken implements TwigComponent
{
    public function template(): string
    {
        return view('components/card')->name();
    }

    public function context(): array
    {
        throw new RuntimeException('boom from context');
    }
}
