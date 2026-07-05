<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Fixtures\CoreComponents;

use Spatie\LaravelData\Data;

/**
 * Презентационная фикстура (spatie/laravel-data) для core-тестов DISCOVERY: реестр
 * находит Data-классы через reflection без Laravel-приложения. Рендер Data-компонентов —
 * фича Laravel-интеграции и тестируется в Laravel-сьюте. Имя: note.
 */
class Note extends Data
{
    public function __construct(
        public string $text = '',
    ) {}

    public function template(): string
    {
        return 'components/note.html.twig';
    }
}
