<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Core;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/** Минимальный PSR-11 контейнер для проверки автовайринга NativeComponentFactory. */
final class FakeContainer implements ContainerInterface
{
    /** @param array<string, object> $services */
    public function __construct(private readonly array $services = []) {}

    public function get(string $id): object
    {
        return $this->services[$id] ?? throw new class("Сервис не найден") extends \Exception implements NotFoundExceptionInterface, ContainerExceptionInterface {};
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
