<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Laravel;

use TTBooking\TwigComponent\ComponentExtension;
use TTBooking\TwigComponent\ComponentRegistry;
use TTBooking\TwigComponent\ComponentRenderingException;
use TTBooking\TwigComponent\Tests\Fixtures\Components\Note;
use Twig\Environment;

/**
 * Laravel-слой: расширение собирается провайдером из контейнера (TwigBridge),
 * рендер идёт через view(), виджеты — через app(), Data — через laravel-data.
 */
class IntegrationTest extends TestCase
{
    private function render(string $source, array $context = []): string
    {
        /** @var Environment $twig */
        $twig = app('twig');

        return $twig->createTemplate($source)->render($context);
    }

    public function test_tag_with_slots_renders_through_twigbridge(): void
    {
        $html = $this->render(
            "{% component 'dialog' with { title: 'T' } %}<p>тело</p>".
            "{% slot 'footer' %}<button>OK</button>{% endslot %}{% endcomponent %}"
        );

        $this->assertStringContainsString('<div class="dialog-body"><p>тело</p></div>', $html);
        $this->assertStringContainsString('<div class="dialog-footer"><button>OK</button></div>', $html);
    }

    public function test_render_component_directly_without_twig_service(): void
    {
        // паттерн тестов компонентов в приложении: прямой вызов из PHP —
        // не требует ни Twig-окружения, ни шаблона-обёртки
        $html = app(ComponentExtension::class)->renderComponent('card', ['title' => 'T']);

        $this->assertStringContainsString('card-title">T<', $html);
    }

    public function test_presentational_data_component_renders(): void
    {
        // ветка Data: Note::from($props) -> all() -> контекст (text доступен напрямую)
        $html = $this->render("{{ component('note', { text: 'привет' }) }}");

        $this->assertStringContainsString('<p class="note">привет</p>', $html);
    }

    public function test_data_component_accepts_map_input_name_alias(): void
    {
        // без учёта MapInputName валидация props отвергла бы валидный алиас `body`
        $html = $this->render("{{ component('badge', { body: 'алиас' }) }}");

        $this->assertStringContainsString('<p class="note">алиас</p>', $html);
    }

    public function test_widget_receives_service_from_laravel_container(): void
    {
        $html = $this->render("{{ component('profile', { name: 'Егор' }) }}");

        $this->assertStringContainsString('Привет, Егор!', $html);
    }

    public function test_unknown_prop_key_throws_with_prop_name(): void
    {
        $this->expectException(ComponentRenderingException::class);
        $this->expectExceptionMessageMatches('/badprop/');

        $this->render("{{ component('card', { badprop: 1 }) }}");
    }

    public function test_container_registry_reads_manifest_instead_of_scanning(): void
    {
        // прод-путь: реестр из контейнера читает манифест из конфига, живой скан не выполняется
        $manifest = tempnam(sys_get_temp_dir(), 'tcprod');
        file_put_contents($manifest, "<?php\n\nreturn ['fake' => ".var_export(Note::class, true)."];\n");

        config()->set('twig-component.manifest', $manifest);

        try {
            $registry = app(ComponentRegistry::class);

            $this->assertSame(Note::class, $registry->resolve('fake'));
            // 'card' есть только в живом скане — его отсутствие доказывает чтение манифеста
            $this->assertNull($registry->resolve('card'));
        } finally {
            @unlink($manifest);
        }
    }
}
