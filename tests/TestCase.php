<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;
use TTBooking\TwigComponent\ComponentExtension;
use TTBooking\TwigComponent\TwigComponentServiceProvider;
use TwigBridge\ServiceProvider as TwigBridgeServiceProvider;

/**
 * База тестов пакета: Testbench поднимает минимальное Laravel-приложение с TwigBridge и нашим
 * провайдером. БД не нужна — машинерия (парсинг/слоты/реестр/рендер) от неё не зависит.
 *
 * Реестр направлен на фикстуры (tests/Fixtures), а не на прикладные App\View\TwigComponents.
 */
abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,   // презентационные компоненты на spatie/laravel-data
            TwigBridgeServiceProvider::class,
            TwigComponentServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];

        // twig: расширение html.twig и только наше расширение (фикстурам большего не нужно)
        $config->set('twigbridge.twig.extension', 'html.twig');
        $config->set('twigbridge.extensions.enabled', [ComponentExtension::class]);

        // без компилированного кэша: закэшированный в skeleton шаблон пережил бы правку
        // парсера/узлов и тесты проверяли бы старую компиляцию
        $config->set('twigbridge.twig.environment.cache', false);

        // шаблоны компонентов-фикстур
        $config->set('view.paths', [__DIR__.'/Fixtures/views']);

        // реестр → неймспейс и путь фикстур; манифеста нет → живой скан
        $config->set('twig-component.namespace', 'TTBooking\\TwigComponent\\Tests\\Fixtures\\Components\\');
        $config->set('twig-component.path', __DIR__.'/Fixtures/Components');
        $config->set('twig-component.manifest', sys_get_temp_dir().'/twig-component-test-manifest.php');
    }
}
