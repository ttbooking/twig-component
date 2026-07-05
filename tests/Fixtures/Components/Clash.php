<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\Components;

use TTBooking\TwigComponent\TwigComponent;

/**
 * Фикстура с зарезервированным ключом `slots` в context() — проверяет guard
 * assertNoReservedContextKeys. Имя: clash.
 */
class Clash implements TwigComponent
{
    public function template(): string
    {
        return view('components/note')->name();
    }

    public function context(): array
    {
        return ['slots' => 'перекрыл бы машинерию'];
    }
}
