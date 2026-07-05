<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\Components;

use TTBooking\TwigComponent\Tests\Fixtures\Support\Greeter;
use TTBooking\TwigComponent\TwigComponent;

/**
 * Фикстура-виджет с DI-зависимостью: сервис из контейнера + скалярный проп через app().
 * Имя: profile.
 */
class Profile implements TwigComponent
{
    public function __construct(
        private readonly Greeter $greeter,
        public string $name = '',
    ) {}

    public function template(): string
    {
        return view('components/note')->name();
    }

    public function context(): array
    {
        return ['text' => $this->greeter->greet($this->name)];
    }
}
