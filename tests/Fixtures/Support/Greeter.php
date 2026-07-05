<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\Support;

/** Сервис-зависимость для проверки контейнерной инъекции в виджет-компонент (Profile). */
class Greeter
{
    public function greet(string $name): string
    {
        return "Привет, {$name}!";
    }
}
