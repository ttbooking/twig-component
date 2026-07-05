<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

/**
 * Рендер шаблона компонента — точка интеграции с view-слоем приложения.
 *
 * Standalone-реализация (TwigTemplateRenderer) рендерит через Twig-окружение, переданное
 * ей в конструктор; Laravel-реализация (Laravel\LaravelViewRenderer) идёт через view(),
 * чтобы работали Laravel-имена вьюх и конвенции TwigBridge.
 *
 * Что означает $template — определяет реализация (путь Twig-шаблона или view-имя);
 * компоненты приложения возвращают из template() имя, понятное активному рендереру.
 */
interface TemplateRenderer
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function render(string $template, array $context): string;
}
