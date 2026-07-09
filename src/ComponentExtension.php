<?php

declare(strict_types=1);

namespace TTBooking\TwigComponent;

use InvalidArgumentException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Расширение Twig для класс-компонентов. Ядро фреймворк-нейтрально: инстанцирование
 * компонента делегировано ComponentFactory, рендер шаблона — TemplateRenderer.
 *
 * Два вида компонентов:
 *  - виджет (TwigComponent) — зависимости через фабрику, сам собирает данные в context();
 *  - презентационный (Spatie\LaravelData\Data с template()) — чистые props; доступен
 *    только с Laravel-фабрикой (сам laravel-data вне Laravel не работает).
 *
 * Шаблон компонента рендерится в изолированном контексте: только данные компонента
 * и `this`, внешний скоуп вызывающего шаблона не протекает.
 */
class ComponentExtension extends AbstractExtension
{
    /**
     * @param  string|null  $basePath  корень приложения — срезается из путей в сообщениях об ошибках
     */
    public function __construct(
        private readonly ComponentRegistry $registry,
        private readonly ComponentFactory $factory,
        private readonly TemplateRenderer $renderer,
        private readonly ?string $basePath = null,
    ) {}

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
     * Публичный вход рендера: тег, функция component() и прямые вызовы из PHP
     * (например, в тестах компонентов приложения) — все идут сюда.
     *
     * @param  array<string, string|\Twig\Markup>  $slots  захваченное тело тега {% component %} по имени
     *                                                     слота (Markup: уже экранировано в скоупе
     *                                                     вызывающего, вставляется без повторного эскейпа);
     *                                                     у функции component() пуст
     */
    public function renderComponent(string $name, array $props = [], array $slots = []): string
    {
        $componentClass = $this->registry->resolve($name)
            ?? throw new InvalidArgumentException("Неизвестный twig-компонент: {$name}");

        // стек вложенности — чтобы ошибка глубоко в дереве компонентов сразу
        // называла всю цепочку, а не терялась в общем стеке Twig
        $this->renderStack[] = $name;

        try {
            $component = $this->factory->create($componentClass, $props);

            // фабрика гарантирует один из двух видов: виджет или Data (у того контекст — all())
            $context = $component instanceof TwigComponent
                ? $component->context()
                : $component->all();

            $this->assertNoReservedContextKeys($componentClass, $name, $context);

            return $this->renderer->render(
                $component->template(),
                ['this' => $component, 'slots' => $slots] + $context,
            );
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
     * Точка исходной ошибки для сообщения: инструменты вроде Ignition показывают стек
     * только внешнего исключения, до previous из интерфейса не добраться — файл:строку
     * оригинала впечатываем в текст.
     */
    private function exceptionOrigin(\Throwable $e): string
    {
        if ($e instanceof \Twig\Error\Error && $e->getSourceContext() !== null) {
            return $e->getSourceContext()->getName().':'.$e->getTemplateLine();
        }

        $file = $e->getFile();

        if ($this->basePath !== null && str_starts_with($file, $this->basePath.DIRECTORY_SEPARATOR)) {
            $file = substr($file, strlen($this->basePath) + 1);
        }

        return $file.':'.$e->getLine();
    }

    /**
     * Ключи `this` и `slots` в контексте рендера зарезервированы за машинерией (экземпляр
     * компонента и переданные слоты): одноимённый ключ из context()/пропов молча перекрылся
     * бы — вместо тихой потери данных падаем с внятным сообщением.
     */
    protected function assertNoReservedContextKeys(string $componentClass, string $name, array $context): void
    {
        if ($reserved = array_intersect(['this', 'slots'], array_keys($context))) {
            throw new InvalidArgumentException(sprintf(
                'Контекст компонента %s (%s) использует зарезервированные ключи [%s] — переименуйте их.',
                $name, $componentClass, implode(', ', $reserved),
            ));
        }
    }
}
