<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\CoreComponents;

use TTBooking\TwigComponent\TwigComponent;

/**
 * Core-фикстура с зарезервированным ключом `slots` в context() — проверяет guard
 * assertNoReservedContextKeys. Имя: clash.
 */
class Clash implements TwigComponent
{
    public function template(): string
    {
        return 'components/note.html.twig';
    }

    public function context(): array
    {
        return ['slots' => 'перекрыл бы машинерию'];
    }
}
