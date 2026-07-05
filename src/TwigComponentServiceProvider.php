<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use Illuminate\Support\ServiceProvider;
use TTBooking\TwigComponent\Console\CacheTwigComponent;
use TTBooking\TwigComponent\Console\ClearTwigComponent;

/**
 * Регистрация машинерии twig-компонентов (см. docs/adr/2026-07-05-twig-components-package.md).
 *
 * Само Twig-расширение (ComponentExtension) подключается приложением в config/twigbridge.php —
 * TwigBridge резолвит его из контейнера, автовайря сюда забинженный ComponentRegistry.
 */
class TwigComponentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/twig-component.php', 'twig-component');

        // singleton: карта компонентов мемоизируется один раз на процесс (см. ComponentRegistry).
        // Неймспейс/пути — из конфига приложения; пакет не знает про App\.
        $this->app->singleton(ComponentRegistry::class, static fn ($app) => new ComponentRegistry(
            $app['config']->get('twig-component.namespace'),
            $app['config']->get('twig-component.path'),
            $app['config']->get('twig-component.manifest'),
        ));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/twig-component.php' => config_path('twig-component.php'),
        ], 'twig-component-config');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([CacheTwigComponent::class, ClearTwigComponent::class]);

        // манифест реестра пересобирается в php artisan optimize и чистится в optimize:clear
        $this->optimizes(
            optimize: 'twig-component:cache',
            clear: 'twig-component:clear',
        );
    }
}
