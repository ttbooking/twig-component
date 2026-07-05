<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use Twig\Environment;

/**
 * Стандартный рендерер: шаблон компонента — обычный Twig-шаблон того же окружения
 * (template() возвращает путь для загрузчика, напр. 'components/card.html.twig').
 */
final class TwigTemplateRenderer implements TemplateRenderer
{
    public function render(Environment $env, string $template, array $context): string
    {
        return $env->render($template, $context);
    }
}
