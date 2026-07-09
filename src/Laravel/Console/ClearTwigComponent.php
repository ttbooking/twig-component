<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Laravel\Console;

use Illuminate\Console\Command;
use TTBooking\TwigComponent\ComponentRegistry;

/**
 * Удаляет манифест реестра twig-компонентов. Врезан в php artisan optimize:clear
 * (см. TwigComponentServiceProvider::boot).
 */
class ClearTwigComponent extends Command
{
    protected $signature = 'twig-component:clear';

    protected $description = 'Удалить манифест реестра twig-компонентов';

    public function handle(ComponentRegistry $registry): int
    {
        $this->components->info($registry->clearCache()
            ? 'Манифест реестра twig-компонентов удалён'
            : 'Манифеста реестра twig-компонентов нет — удалять нечего');

        return self::SUCCESS;
    }
}
