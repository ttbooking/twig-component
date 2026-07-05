<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use Twig\Error\RuntimeError;

/**
 * Ошибка рендера twig-компонента: сообщение называет компонент (цепочку
 * вложенности) и файл:строку исходной ошибки; оригинал — в previous.
 *
 * Наследует Twig\Error\RuntimeError сознательно: свои исключения Twig
 * поднимает без обёртки «An exception has been thrown during the rendering…»,
 * только аннотируя шаблоном и строкой вызова component() — TwigBridge затем
 * показывает их как точку ошибки (ErrorException с twig-файлом и строкой).
 */
class ComponentRenderingException extends RuntimeError {}
