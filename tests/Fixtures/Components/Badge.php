<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\Components;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

/**
 * Презентационная фикстура с MapInputName: проп подаётся под алиасом `body` —
 * проверяет, что assertKnownProps знает алиасы laravel-data. Имя: badge.
 */
class Badge extends Data
{
    public function __construct(
        #[MapInputName('body')]
        public string $text = '',
    ) {}

    public function template(): string
    {
        return view('components/note')->name();
    }
}
