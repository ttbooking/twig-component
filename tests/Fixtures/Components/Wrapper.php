<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\Components;

use TTBooking\TwigComponent\TwigComponent;

/**
 * Фикстура-виджет, чей шаблон рендерит вложенный компонент broken — для проверки
 * цепочки вложенности в сообщении ComponentRenderingException. Имя: wrapper.
 */
class Wrapper implements TwigComponent
{
    public function template(): string
    {
        return view('components/wrapper')->name();
    }

    public function context(): array
    {
        return [];
    }
}
