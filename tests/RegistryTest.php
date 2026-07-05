<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests;

use TTBooking\TwigComponent\ComponentRegistry;
use TTBooking\TwigComponent\Tests\Fixtures\Components\Card;
use TTBooking\TwigComponent\Tests\Fixtures\Components\Dialog;
use TTBooking\TwigComponent\Tests\Fixtures\Components\Forms\Field;
use TTBooking\TwigComponent\Tests\Fixtures\Components\Note;
use TTBooking\TwigComponent\Tests\Fixtures\Components\UI\Widget;

class RegistryTest extends TestCase
{
    private const NS = 'TTBooking\\TwigComponent\\Tests\\Fixtures\\Components\\';

    private function registry(?string $manifest = null): ComponentRegistry
    {
        return new ComponentRegistry(
            self::NS,
            __DIR__.'/Fixtures/Components',
            $manifest ?? sys_get_temp_dir().'/tc-'.uniqid().'.php',
        );
    }

    public function test_derive_name_follows_convention(): void
    {
        $registry = $this->registry();

        $this->assertSame('card', $registry->deriveName(Card::class));
        $this->assertSame('dialog', $registry->deriveName(Dialog::class));
        // акроним зоны: lower('UI')='ui', а не kebab('UI')='u-i'
        $this->assertSame('ui:widget', $registry->deriveName(Widget::class));
        $this->assertSame('forms:field', $registry->deriveName(Field::class));
        $this->assertSame('note', $registry->deriveName(Note::class));
    }

    public function test_discovery_finds_widget_and_data_fixtures(): void
    {
        $map = $this->registry()->build();

        $this->assertSame(Card::class, $map['card'] ?? null);
        $this->assertSame(Widget::class, $map['ui:widget'] ?? null);
        $this->assertSame(Field::class, $map['forms:field'] ?? null);
        // презентационная (Spatie Data) ветка discovery
        $this->assertSame(Note::class, $map['note'] ?? null);
        $this->assertArrayHasKey('dialog', $map);
    }

    public function test_every_discovered_name_round_trips_through_convention(): void
    {
        $registry = $this->registry();

        foreach ($registry->build() as $name => $class) {
            $this->assertSame($name, $registry->deriveName($class), "имя {$name} не выводится из {$class}");
        }
    }

    public function test_manifest_round_trips_identically(): void
    {
        $manifest = tempnam(sys_get_temp_dir(), 'tcmanifest').'.php';

        try {
            $built = $this->registry($manifest)->cache();

            $this->assertFileExists($manifest);

            $reader = $this->registry($manifest);
            $this->assertTrue($reader->isCached());
            $this->assertSame($built, $reader->map());
        } finally {
            @unlink($manifest);
        }
    }

    public function test_override_wins_over_convention(): void
    {
        // несуществующий путь → скан пуст; override — единственный источник карты
        $registry = new class(self::NS, '/does-not-exist', '/does-not-exist') extends ComponentRegistry
        {
            protected function overrides(): array
            {
                return ['card' => Note::class];
            }
        };

        $this->assertSame(Note::class, $registry->resolve('card'));
    }
}
