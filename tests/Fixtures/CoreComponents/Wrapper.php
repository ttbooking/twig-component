<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\CoreComponents;

use TTBooking\TwigComponent\TwigComponent;

/**
 * Core-фикстура, чей шаблон рендерит вложенный компонент broken — для проверки
 * цепочки вложенности в сообщении ComponentRenderingException. Имя: wrapper.
 */
class Wrapper implements TwigComponent
{
    public function template(): string
    {
        return 'components/wrapper.html.twig';
    }

    public function context(): array
    {
        return [];
    }
}
