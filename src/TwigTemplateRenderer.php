<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use Twig\Environment;

/**
 * Стандартный рендерер: шаблон компонента — обычный Twig-шаблон переданного окружения
 * (template() возвращает путь для загрузчика, напр. 'components/card.html.twig').
 *
 * Окружение принимается и замыканием — на случай, когда рендерер нужно собрать раньше
 * самого Environment (например, при конфигурации через DI-контейнер).
 */
final class TwigTemplateRenderer implements TemplateRenderer
{
    /** @param  Environment|\Closure(): Environment  $twig */
    public function __construct(private readonly Environment|\Closure $twig) {}

    public function render(string $template, array $context): string
    {
        $twig = $this->twig instanceof Environment ? $this->twig : ($this->twig)();

        return $twig->render($template, $context);
    }
}
