<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

/**
 * Виджет-компонент Twig: сам собирает данные для своего шаблона.
 *
 * Зависимости получает через конструктор (их источник определяет ComponentFactory:
 * контейнер Laravel, PSR-11 или props-only new), данные отдаёт через context().
 * Шаблон рендерится в изолированном контексте: переменными становятся только context()
 * и сам компонент (`this`), внешний скоуп вызывающего шаблона не протекает.
 *
 * Презентационные компоненты (чистые props) — отдельный вид: класс на Spatie\LaravelData\Data
 * с методом template(); доступны только в Laravel-интеграции (ограничение самого laravel-data).
 */
interface TwigComponent
{
    /**
     * Имя шаблона компонента, понятное активному TemplateRenderer:
     *  - standalone (TwigTemplateRenderer) — путь для загрузчика Twig,
     *    напр. 'components/card.html.twig';
     *  - Laravel (LaravelViewRenderer) — view-имя, напр. 'components/card'.
     *    Возврат через view('...')->name() даёт литерал внутри view(), видимый
     *    Laravel Idea (клик-навигация класс↔шаблон, find usages).
     */
    public function template(): string;

    /**
     * Контекст рендера шаблона — единственный источник его переменных.
     */
    public function context(): array;
}
