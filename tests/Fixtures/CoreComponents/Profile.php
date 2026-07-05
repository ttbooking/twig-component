<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\CoreComponents;

use TTBooking\TwigComponent\Tests\Fixtures\Support\Greeter;
use TTBooking\TwigComponent\TwigComponent;

/**
 * Core-фикстура-виджет с DI-зависимостью: сервис автовайрится NativeComponentFactory
 * из PSR-11 контейнера, скалярный проп — из вызова. Имя: profile.
 */
class Profile implements TwigComponent
{
    public function __construct(
        private readonly Greeter $greeter,
        public string $name = '',
    ) {}

    public function template(): string
    {
        return 'components/note.html.twig';
    }

    public function context(): array
    {
        return ['text' => $this->greeter->greet($this->name)];
    }
}
