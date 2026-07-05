<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

/**
 * Виджет-компонент Twig: сам собирает данные для своего шаблона.
 *
 * Зависимости получает через конструктор из контейнера, данные отдаёт через context().
 * Шаблон рендерится в изолированном контексте: переменными становятся только context()
 * и сам компонент (`this`), внешний скоуп вызывающего шаблона не протекает.
 *
 * Презентационные компоненты (чистые props) — отдельный вид: класс на Spatie\LaravelData\Data
 * с методом template(); см. ComponentExtension::renderComponent().
 */
interface TwigComponent
{
    /**
     * Laravel view-имя шаблона компонента (каталог files/templates/components/).
     *
     * Возвращается через view('...')->name(): литерал внутри view() виден Laravel Idea
     * (клик-навигация класс↔шаблон, проверка существования, find usages). После релиза
     * laravel-idea/plugin#1365 обёртку view() можно убрать и возвращать голую строку.
     */
    public function template(): string;

    /**
     * Контекст рендера шаблона — единственный источник его переменных.
     */
    public function context(): array;
}
