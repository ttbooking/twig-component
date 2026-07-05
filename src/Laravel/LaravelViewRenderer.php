<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Laravel;

use TTBooking\TwigComponent\TemplateRenderer;

/**
 * Laravel-рендерер: шаблон компонента — Laravel-вьюха (template() возвращает view-имя,
 * напр. 'components/card'); резолв имени и рендер идут через view() и TwigBridge.
 * Twig-окружение ему не нужно — прямые вызовы renderComponent() работают и без него.
 */
class LaravelViewRenderer implements TemplateRenderer
{
    public function render(string $template, array $context): string
    {
        return view($template, $context)->render();
    }
}
