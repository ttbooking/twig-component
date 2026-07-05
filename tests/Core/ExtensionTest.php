<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Core;

use InvalidArgumentException;
use RuntimeException;
use TTBooking\TwigComponent\ComponentExtension;
use TTBooking\TwigComponent\ComponentRenderingException;
use TTBooking\TwigComponent\NativeComponentFactory;
use TTBooking\TwigComponent\Tests\Fixtures\CoreComponents\Broken;
use TTBooking\TwigComponent\Tests\Fixtures\CoreComponents\Note;
use TTBooking\TwigComponent\Tests\Fixtures\Support\Greeter;

/** Расширение на голом Twig: резолв, валидация props, DI, обёртка ошибок, inline-функция. */
class ExtensionTest extends CoreTestCase
{
    private function extension(): ComponentExtension
    {
        return $this->twig()->getExtension(ComponentExtension::class);
    }

    public function test_unknown_component_throws_invalid_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->extension()->renderComponent('no-such-component');
    }

    public function test_unknown_prop_key_throws_with_prop_name(): void
    {
        $this->expectException(ComponentRenderingException::class);
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
            // файл:строка исходной ошибки впечатаны в сообщение (previous в трейсерах не виден)
            $this->assertMatchesRegularExpression('/Broken\.php:\d+\]/', $e->getMessage());
            $this->assertInstanceOf(RuntimeException::class, $e->getPrevious());
            $this->assertSame('boom from context', $e->getPrevious()->getMessage());
        }
    }

    public function test_component_function_works_inside_twig_template(): void
    {
        $html = $this->render("{{ component('card', { title: 'T' }) }}");

        $this->assertStringContainsString('card-title">T<', $html);
    }

    public function test_widget_receives_service_from_psr11_container(): void
    {
        $container = new FakeContainer([Greeter::class => new Greeter]);

        $html = $this->render("{{ component('profile', { name: 'Егор' }) }}", [], $container);

        $this->assertStringContainsString('Привет, Егор!', $html);
    }

    public function test_widget_with_dependency_fails_clearly_without_container(): void
    {
        $this->expectException(ComponentRenderingException::class);

        // NativeComponentFactory без контейнера — Greeter нечем разрешить
        $this->extension()->renderComponent('profile', ['name' => 'Егор']);
    }

    public function test_nested_component_error_message_names_full_chain(): void
    {
        try {
            $this->render("{{ component('wrapper') }}");
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

    public function test_native_factory_rejects_data_components(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Laravel/');

        (new NativeComponentFactory)->create(Note::class, []);
    }
}
