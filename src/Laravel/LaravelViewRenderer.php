<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Laravel;

use TTBooking\TwigComponent\TemplateRenderer;
use Twig\Environment;

/**
 * Laravel-рендерер: шаблон компонента — Laravel-вьюха (template() возвращает view-имя,
 * напр. 'components/card'); резолв имени и рендер идут через view() и TwigBridge.
 */
class LaravelViewRenderer implements TemplateRenderer
{
    public function render(Environment $env, string $template, array $context): string
    {
        return view($template, $context)->render();
    }
}
