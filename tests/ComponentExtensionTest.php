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

    public function test_component_function_works_inside_twig_template(): void
    {
        // сама регистрация TwigFunction: inline-вызов из реального шаблона, props экранируются
        $html = app('twig')->createTemplate("{{ component('note', { text: 'x <b>y</b>' }) }}")->render();

        $this->assertStringContainsString('<p class="note">x &lt;b&gt;y&lt;/b&gt;</p>', $html);
    }

    public function test_widget_receives_service_from_container_and_prop_from_call(): void
    {
        $html = $this->extension()->renderComponent('profile', ['name' => 'Егор']);

        $this->assertStringContainsString('Привет, Егор!', $html);
    }

    public function test_nested_component_error_message_names_full_chain(): void
    {
        // через реальный Twig-рендер: и wrapper, и вложенный broken идут через
        // один экземпляр расширения из окружения — стек вложенности общий
        try {
            app('twig')->createTemplate("{{ component('wrapper') }}")->render();
            $this->fail('Ожидалась ComponentRenderingException');
        } catch (ComponentRenderingException $e) {
            $this->assertStringContainsString('wrapper -> broken', $e->getMessage());
            $this->assertStringContainsString('boom from context', $e->getMessage());
        }
    }

    public function test_reserved_context_key_throws_instead_of_silent_override(): void
    {
        $this->expectException(ComponentRenderingException::class);
        $this->expectExceptionMessageMatches('/зарезервированные ключи \[slots\]/u');

        $this->extension()->renderComponent('clash');
    }

    public function test_data_component_accepts_map_input_name_alias(): void
    {
        // без учёта MapInputName валидация props отвергла бы валидный алиас `body`
        $html = $this->extension()->renderComponent('badge', ['body' => 'алиас']);

        $this->assertStringContainsString('<p class="note">алиас</p>', $html);
    }
}
