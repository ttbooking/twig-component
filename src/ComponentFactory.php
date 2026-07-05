<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

/**
 * Инстанцирование класс-компонента из props вызова — точка интеграции с фреймворком.
 *
 * Ядро пакета не знает, как приложение собирает объекты: standalone-реализация
 * (NativeComponentFactory) делает new + опциональный PSR-11 автовайринг, Laravel-реализация
 * (Laravel\LaravelComponentFactory) — app($class, $props) и Data::from() для laravel-data.
 *
 * Контракт: возвращается либо TwigComponent (виджет), либо презентационный компонент
 * с методами template()/all() (Spatie Data). Неизвестные ключи props — InvalidArgumentException:
 * молчаливое игнорирование опечатки оставило бы проп с дефолтом без какой-либо ошибки.
 */
interface ComponentFactory
{
    /**
     * @param  class-string  $componentClass
     * @param  array<string, mixed>  $props
     */
    public function create(string $componentClass, array $props): object;
}
