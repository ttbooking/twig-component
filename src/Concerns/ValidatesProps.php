<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Concerns;

use InvalidArgumentException;

/**
 * Общая для фабрик валидация props (см. issue #1: логика была продублирована
 * в NativeComponentFactory и LaravelComponentFactory с расходящимся текстом ошибки).
 *
 * Ключ props, не совпадающий ни с одним известным пропом компонента, — опечатка:
 * и Data::from(), и контейнер, и именованные аргументы с дефолтами молча игнорируют
 * неизвестные ключи, из-за чего проп с дефолтом остаётся дефолтным без какой-либо ошибки.
 */
trait ValidatesProps
{
    /**
     * @param  array<string, mixed>  $props
     * @param  list<string>  $allowed
     */
    protected function assertKnownProps(string $componentClass, array $props, array $allowed): void
    {
        if ($unknown = array_diff(array_keys($props), $allowed)) {
            throw new InvalidArgumentException(sprintf(
                'Неизвестные props [%s] у компонента %s; компонент принимает: [%s]',
                implode(', ', $unknown), $componentClass, implode(', ', $allowed),
            ));
        }
    }

    /**
     * Допустимые имена props виджета — параметры конструктора. DI-зависимости в списке
     * неизбежны (по имени параметра сервис от пропа не отличить), но попытка передать их
     * пропом упадёт дальше на инстанцировании с внятной обёрткой.
     *
     * @return list<string>
     */
    protected function constructorPropNames(string $componentClass): array
    {
        $constructor = (new \ReflectionClass($componentClass))->getConstructor();

        return $constructor
            ? array_map(static fn ($parameter) => $parameter->getName(), $constructor->getParameters())
            : [];
    }
}
