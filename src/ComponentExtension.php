<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\LaravelData\Data;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Расширение Twig для класс-компонентов (см. docs/adr/2026-07-02-twig-components.md).
 *
 * Два вида компонентов:
 *  - виджет (TwigComponent) — зависимости из контейнера, сам собирает данные;
 *  - презентационный (Spatie\LaravelData\Data со статическим template()) — чистые props,
 *    Data::from() даёт кастинг и ошибку при опечатке в имени пропа.
 *
 * Шаблон компонента рендерится в изолированном контексте: только данные компонента
 * и `this`, внешний скоуп вызывающего шаблона не протекает.
 */
class ComponentExtension extends AbstractExtension
{
    public function __construct(private readonly ComponentRegistry $registry) {}

    /**
     * Стек имён рендерящихся компонентов (вложенные рендеры) — для сообщений об ошибках.
     *
     * @var string[]
     */
    private array $renderStack = [];

    public function getName(): string
    {
        return 'ComponentExtension';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [$this, 'renderComponent'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Тег {% component %}…{% endcomponent %} для компонентов со слотом (тело вызова).
     * Функция component() остаётся для бестелых вызовов; оба маршрутизируются в renderComponent().
     */
    public function getTokenParsers(): array
    {
        return [new ComponentTokenParser, new SlotTokenParser];
    }

    /**
     * @param  array<string, string>  $slots  захваченное тело тега {% component %} по имени слота
     *                                        (сейчас только 'content'); у функции component() пуст
     */
    public function renderComponent(string $name, array $props = [], array $slots = []): string
    {
        $componentClass = $this->registry->resolve($name)
            ?? throw new InvalidArgumentException("Неизвестный twig-компонент: {$name}");

        $this->assertKnownProps($componentClass, $name, $props);

        // стек вложенности — чтобы ошибка глубоко в дереве компонентов сразу
        // называла всю цепочку, а не терялась в общем стеке Twig/Laravel
        $this->renderStack[] = $name;

        try {
            if (is_subclass_of($componentClass, Data::class)) {
                $component = $componentClass::from($props);
                $context = $component->all();
            } elseif (is_subclass_of($componentClass, TwigComponent::class)) {
                $component = app($componentClass, $props);
                $context = $component->context();
            } else {
                throw new InvalidArgumentException(
                    "Класс {$componentClass} компонента {$name} не реализует TwigComponent и не является Data"
                );
            }

            // template() отдаёт имя через view('...')->name() (литерал ради IDE-навигации);
            // получение имени пренебрежимо на фоне сборки данных в context()
            return view($component->template(), ['this' => $component, 'slots' => $slots] + $context)->render();
        } catch (ComponentRenderingException $e) {
            // вложенный компонент уже описал себя (и цепочка в его сообщении полнее)
            throw $e;
        } catch (\Throwable $e) {
            throw new ComponentRenderingException(
                sprintf(
                    "Ошибка рендера компонента '%s' (%s, цепочка: %s): %s [%s]",
                    $name,
                    $componentClass,
                    implode(' -> ', $this->renderStack),
                    $e->getMessage(),
                    $this->exceptionOrigin($e),
                ),
                previous: $e,
            );
        } finally {
            array_pop($this->renderStack);
        }
    }

    /**
     * Точка исходной ошибки для сообщения: Ignition показывает стек только
     * внешнего исключения, до previous из интерфейса не добраться — файл:строку
     * оригинала впечатываем в текст.
     */
    private function exceptionOrigin(\Throwable $e): string
    {
        if ($e instanceof \Twig\Error\Error && $e->getSourceContext() !== null) {
            return $e->getSourceContext()->getName().':'.$e->getTemplateLine();
        }

        return Str::after($e->getFile(), base_path().DIRECTORY_SEPARATOR).':'.$e->getLine();
    }

    /**
     * Ключ props, не совпадающий ни с одним параметром конструктора компонента, — опечатка:
     * и Data::from(), и контейнер молча игнорируют неизвестные ключи, из-за чего проп
     * с дефолтом остаётся дефолтным без какой-либо ошибки.
     */
    protected function assertKnownProps(string $componentClass, string $name, array $props): void
    {
        if ($props === []) {
            return;
        }

        $constructor = (new \ReflectionClass($componentClass))->getConstructor();
        $allowed = $constructor
            ? array_map(fn ($parameter) => $parameter->getName(), $constructor->getParameters())
            : [];

        if ($unknown = array_diff(array_keys($props), $allowed)) {
            throw new InvalidArgumentException(sprintf(
                'Неизвестные props [%s] у компонента %s; конструктор %s принимает: [%s]',
                implode(', ', $unknown), $name, $componentClass, implode(', ', $allowed),
            ));
        }
    }
}
