<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\Components;

use Spatie\LaravelData\Data;

/** Презентационная фикстура на spatie/laravel-data — проверяет Data-ветку discovery и рендера. Имя: note. */
class Note extends Data
{
    public function __construct(
        public string $text = '',
    ) {}

    public function template(): string
    {
        return view('components/note')->name();
    }
}
