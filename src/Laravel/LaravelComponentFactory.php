<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Laravel;

use InvalidArgumentException;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use TTBooking\TwigComponent\ComponentFactory;
use TTBooking\TwigComponent\Concerns\ValidatesProps;
use TTBooking\TwigComponent\TwigComponent;

/**
 * Laravel-фабрика компонентов: виджеты — через контейнер (app($class, $props) даёт DI
 * зависимостей + именованные props), презентационные — через Data::from() (кастинг
 * и MapInputName от laravel-data).
 */
class LaravelComponentFactory implements ComponentFactory
{
    use ValidatesProps;

    public function create(string $componentClass, array $props): object
    {
        if (is_subclass_of($componentClass, Data::class)) {
            $this->assertKnownProps($componentClass, $props, $this->dataPropNames($componentClass));

            return $componentClass::from($props);
        }

        if (is_subclass_of($componentClass, TwigComponent::class)) {
            $this->assertKnownProps($componentClass, $props, $this->constructorPropNames($componentClass));

            return app($componentClass, $props);
        }

        throw new InvalidArgumentException(
            "Класс {$componentClass} не реализует TwigComponent и не является Data"
        );
    }

    /**
     * Допустимые имена props Data-компонента — из структуры laravel-data (свойства +
     * их MapInputName-алиасы, включая non-promoted): reflection конструктора их не видит.
     *
     * @return list<string>
     */
    protected function dataPropNames(string $componentClass): array
    {
        $allowed = [];

        foreach (app(DataConfig::class)->getDataClass($componentClass)->properties as $property) {
            $allowed[] = $property->name;

            if ($property->inputMappedName !== null) {
                $allowed[] = $property->inputMappedName;
            }
        }

        return $allowed;
    }
}
