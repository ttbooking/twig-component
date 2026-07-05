<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Console;

use Illuminate\Console\Command;
use TTBooking\TwigComponent\ComponentRegistry;

/**
 * Компилирует реестр twig-компонентов в манифест bootstrap/cache/twig-component.php.
 * Врезан в php artisan optimize (см. TwigComponentServiceProvider::boot).
 */
class CacheTwigComponent extends Command
{
    protected $signature = 'twig-component:cache';

    protected $description = 'Скомпилировать реестр twig-компонентов в манифест';

    public function handle(ComponentRegistry $registry): int
    {
        $map = $registry->cache();

        $this->components->info(sprintf('Реестр twig-компонентов закэширован (%d шт.)', count($map)));

        return self::SUCCESS;
    }
}
