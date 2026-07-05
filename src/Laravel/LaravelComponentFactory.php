<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Laravel;

use InvalidArgumentException;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use TTBooking\TwigComponent\ComponentFactory;
use TTBooking\TwigComponent\TwigComponent;

/**
 * Laravel-фабрика компонентов: виджеты — через контейнер (app($class, $props) даёт DI
 * зависимостей + именованные props), презентационные — через Data::from() (кастинг
 * и MapInputName от laravel-data).
 */
class LaravelComponentFactory implements ComponentFactory
{
    public function create(string $componentClass, array $props): object
    {
        $this->assertKnownProps($componentClass, $props);

        if (is_subclass_of($componentClass, Data::class)) {
            return $componentClass::from($props);
        }

        if (is_subclass_of($componentClass, TwigComponent::class)) {
            return app($componentClass, $props);
        }

        throw new InvalidArgumentException(
            "Класс {$componentClass} не реализует TwigComponent и не является Data"
        );
    }

    /**
     * Ключ props, не совпадающий ни с одним известным пропом компонента, — опечатка:
     * и Data::from(), и контейнер молча игнорируют неизвестные ключи, из-за чего проп
     * с дефолтом остаётся дефолтным без какой-либо ошибки.
     *
     * Для Data-компонентов известные имена берутся из структуры laravel-data (свойства +
     * их MapInputName-алиасы, включая non-promoted) — reflection конструктора их не видит.
     * Для виджетов — параметры конструктора; DI-зависимости в списке допустимых неизбежны
     * (по имени параметра сервис от пропа не отличить), но попытка передать их пропом
     * упадёт дальше на инстанцировании с внятной обёрткой.
     */
    protected function assertKnownProps(string $componentClass, array $props): void
    {
        if ($props === []) {
            return;
        }

        if (is_subclass_of($componentClass, Data::class)) {
            $allowed = [];

            foreach (app(DataConfig::class)->getDataClass($componentClass)->properties as $property) {
                $allowed[] = $property->name;

                if ($property->inputMappedName !== null) {
                    $allowed[] = $property->inputMappedName;
                }
            }
        } else {
            $constructor = (new \ReflectionClass($componentClass))->getConstructor();
            $allowed = $constructor
                ? array_map(fn ($parameter) => $parameter->getName(), $constructor->getParameters())
                : [];
        }

        if ($unknown = array_diff(array_keys($props), $allowed)) {
            throw new InvalidArgumentException(sprintf(
                'Неизвестные props [%s] у компонента %s; принимает: [%s]',
                implode(', ', $unknown), $componentClass, implode(', ', $allowed),
            ));
        }
    }
}
