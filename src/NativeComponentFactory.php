<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use TTBooking\TwigComponent\Concerns\ValidatesProps;

/**
 * Standalone-фабрика компонентов: new $class(...$props) по именованным аргументам.
 *
 * Если передан PSR-11 контейнер, параметры конструктора, не покрытые props и без
 * дефолта, автовайрятся из него по типу (DI-зависимости виджетов). Без контейнера
 * виджеты ограничены props-only конструкторами.
 *
 * Data-компоненты (spatie/laravel-data) намеренно не поддерживаются: сам laravel-data
 * не работает вне Laravel — используйте Laravel-интеграцию пакета.
 */
class NativeComponentFactory implements ComponentFactory
{
    use ValidatesProps;

    public function __construct(private readonly ?ContainerInterface $container = null) {}

    public function create(string $componentClass, array $props): object
    {
        if (! is_subclass_of($componentClass, TwigComponent::class)) {
            throw new InvalidArgumentException(sprintf(
                'NativeComponentFactory поддерживает только TwigComponent-виджеты; %s им не является'
                .' (Data-компоненты доступны в Laravel-интеграции).',
                $componentClass,
            ));
        }

        $constructor = (new ReflectionClass($componentClass))->getConstructor();
        $parameters = $constructor?->getParameters() ?? [];

        $this->assertKnownProps($componentClass, $props, array_map(
            static fn ($parameter) => $parameter->getName(),
            $parameters,
        ));

        $arguments = $props;

        foreach ($parameters as $parameter) {
            if (array_key_exists($parameter->getName(), $arguments)
                || $parameter->isDefaultValueAvailable()
                || $parameter->isVariadic()) {
                continue;
            }

            $type = $parameter->getType();

            if ($this->container !== null
                && $type instanceof ReflectionNamedType
                && ! $type->isBuiltin()
                && $this->container->has($type->getName())) {
                $arguments[$parameter->getName()] = $this->container->get($type->getName());
            }
        }

        return new $componentClass(...$arguments);
    }
}
