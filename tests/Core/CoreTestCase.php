<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Core;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use TTBooking\TwigComponent\ComponentExtension;
use TTBooking\TwigComponent\ComponentRegistry;
use TTBooking\TwigComponent\NativeComponentFactory;
use TTBooking\TwigComponent\TwigTemplateRenderer;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * База core-тестов: голый Twig без какого-либо фреймворка — ядро пакета
 * (теги, слоты, реестр, рендер) обязано работать в таком окружении.
 */
abstract class CoreTestCase extends TestCase
{
    protected const NS = 'TTBooking\\TwigComponent\\Tests\\Fixtures\\CoreComponents\\';

    protected function registry(?string $manifest = null): ComponentRegistry
    {
        return new ComponentRegistry(
            static::NS,
            __DIR__.'/../Fixtures/CoreComponents',
            $manifest ?? sys_get_temp_dir().'/tc-core-'.uniqid().'.php',
        );
    }

    protected function twig(?ContainerInterface $container = null): Environment
    {
        $twig = new Environment(
            new FilesystemLoader(__DIR__.'/../Fixtures/views'),
            ['cache' => false],
        );

        $twig->addExtension(new ComponentExtension(
            $this->registry(),
            new NativeComponentFactory($container),
            new TwigTemplateRenderer($twig),
        ));

        return $twig;
    }

    protected function render(string $source, array $context = [], ?ContainerInterface $container = null): string
    {
        return $this->twig($container)->createTemplate($source)->render($context);
    }
}
