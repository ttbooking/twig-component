<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use Twig\Environment;

/**
 * Рендер шаблона компонента — точка интеграции с view-слоем приложения.
 *
 * Standalone-реализация (TwigTemplateRenderer) рендерит напрямую через переданное
 * Twig-окружение; Laravel-реализация (Laravel\LaravelViewRenderer) идёт через view(),
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
    public function render(Environment $env, string $template, array $context): string;
}
