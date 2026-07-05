<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests;

use InvalidArgumentException;
use RuntimeException;
use TTBooking\TwigComponent\ComponentExtension;
use TTBooking\TwigComponent\ComponentRenderingException;
use TTBooking\TwigComponent\Tests\Fixtures\Components\Broken;

class ComponentExtensionTest extends TestCase
{
    private function extension(): ComponentExtension
    {
        return app(ComponentExtension::class);
    }

    public function test_unknown_component_throws_invalid_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->extension()->renderComponent('no-such-component');
    }

    public function test_unknown_prop_key_throws_with_prop_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/badprop/');

        $this->extension()->renderComponent('card', ['badprop' => 1]);
    }

    public function test_error_in_component_is_wrapped_and_keeps_original(): void
    {
        try {
            $this->extension()->renderComponent('broken');
            $this->fail('Ожидалась ComponentRenderingException');
        } catch (ComponentRenderingException $e) {
            $this->assertStringContainsString('broken', $e->getMessage());
            $this->assertStringContainsString(Broken::class, $e->getMessage());
            $this->assertStringContainsString('boom from context', $e->getMessage());
            // файл:строка исходной ошибки впечатаны в сообщение (previous в Ignition не виден)
            $this->assertMatchesRegularExpression('/Broken\.php:\d+\]/', $e->getMessage());
            $this->assertInstanceOf(RuntimeException::class, $e->getPrevious());
            $this->assertSame('boom from context', $e->getPrevious()->getMessage());
        }
    }

    public function test_presentational_data_component_renders(): void
    {
        // ветка Data: Note::from($props) -> all() -> контекст (text доступен напрямую)
        $html = $this->extension()->renderComponent('note', ['text' => 'привет']);

        $this->assertStringContainsString('<p class="note">привет</p>', $html);
    }
}
