<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent\Tests\Core;

/**
 * Совместимость с текущим Twig: компиляция и рендер наших тегов не должны опираться
 * на deprecated-API (то, что deprecated в Twig 3, ломается в Twig 4).
 */
class TwigCompatibilityTest extends CoreTestCase
{
    public function test_component_and_slot_tags_emit_no_twig_deprecations(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $no, string $msg) use (&$deprecations): bool {
            if ($no === E_USER_DEPRECATED) {
                $deprecations[] = $msg;

                return true;
            }

            return false;
        });

        try {
            $this->render(
                "{% component 'dialog' with { title: 'T' } %}тело{% slot 'footer' %}f{% endslot %}{% endcomponent %}"
            );
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
    }
}
