<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Laravel;

use Illuminate\Support\ServiceProvider;
use TTBooking\TwigComponent\ComponentExtension;
use TTBooking\TwigComponent\ComponentFactory;
use TTBooking\TwigComponent\ComponentRegistry;
use TTBooking\TwigComponent\Laravel\Console\CacheTwigComponent;
use TTBooking\TwigComponent\Laravel\Console\ClearTwigComponent;
use TTBooking\TwigComponent\TemplateRenderer;

/**
 * Laravel-интеграция twig-компонентов: биндит фреймворк-нейтральное ядро на контейнер
 * (LaravelComponentFactory) и view-слой (LaravelViewRenderer), добавляет artisan-команды
 * манифеста и optimize-хуки.
 *
 * Само Twig-расширение (ComponentExtension) подключается приложением в config/twigbridge.php —
 * TwigBridge резолвит его из контейнера, где наш биндинг собирает его с Laravel-реализациями.
 */
class TwigComponentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/twig-component.php', 'twig-component');

        // singleton: карта компонентов мемоизируется один раз на процесс (см. ComponentRegistry).
        // Неймспейс/пути — из конфига приложения; пакет не знает про App\.
        $this->app->singleton(ComponentRegistry::class, static fn ($app) => new ComponentRegistry(
            $app['config']->get('twig-component.namespace'),
            $app['config']->get('twig-component.path'),
            $app['config']->get('twig-component.manifest'),
        ));

        $this->app->bind(ComponentFactory::class, LaravelComponentFactory::class);
        $this->app->bind(TemplateRenderer::class, LaravelViewRenderer::class);

        $this->app->bind(ComponentExtension::class, static fn ($app) => new ComponentExtension(
            $app[ComponentRegistry::class],
            $app[ComponentFactory::class],
            $app[TemplateRenderer::class],
            $app->basePath(),
        ));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/twig-component.php' => config_path('twig-component.php'),
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
