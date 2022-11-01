<?php

namespace EKvedaras\ClassFactory;

use Closure;
use Illuminate\Support\Collection;

/**
 * @template T
 */
abstract class ClassFactory
{
    /** @var class-string<T> */
    protected string $class;

    /** @var array[]|Closure[] */
    private array $states = [];

    /** @var Closure[] */
    private array $lateTransformers = [];

    /** @return static<T> */
    public static function new(): static
    {
        $factory = new static();

        return $factory->state($factory->definition());
    }

    abstract protected function definition(): array;

    /** @return static<T> */
    public function state(array | callable $state): static
    {
        $this->states[] = $state;

        return $this;
    }

    /** @return static<T> */
    public function after(Closure $transformer): static
    {
        $this->lateTransformers[] = $transformer;

        return $this;
    }

    /** @return T */
    public function make(array | Closure $state = null): object
    {
        if (isset($state)) {
            $this->state($state);
        }

        $object = new $this->class(...$this->collapseStates());

        foreach ($this->lateTransformers as $transformer) {
            $transformer($object);
        }

        return $object;
    }

    private function collapseStates(): array
    {
        $definedProperties = array_flip(array_keys($this->definition()));

        /**
         * Evaluate states that are closures and group all resulting states per property
         *
         *  $this->states = [
         *      ['id' => 1, 'key' => 'a', 'prop' => OtherClassFactory::new(), 'action' = ClosureValue::of(fn () => 123)],
         *      fn () => ['id' => 2],
         *      ['key' => fn ($attrs) => $attrs['key'] . 'b' . $attrs['id']],
         *      ['prop' => fn ($attrs) => $attrs['prop']->someState()],
         *  ];
         * turns into
         *  $collapsedState = [
         *      'id' => [1, 2],
         *      'key' => ['a', fn ($attrs) => $attrs['key'] . 'b' . $attrs['id']],
         *      'prop' => [OtherClassFactory::new(), fn ($attrs) => $attrs['prop']->someState()],
         *      'action' => [ClosureValue::of(fn () => 123)],
         *  ];
         */
        $collapsedState = array_reduce(
            $this->states,
            function (array $carry, array | Closure $state) {
                if ($state instanceof Closure) {
                    $state = $state($carry);
                }

                foreach ($state as $key => $value) {
                    $carry[$key][] = $value;
                }

                return $carry;
            },
            initial: array_map(fn ($value) => [$value], $this->definition()),
        );

        /**
         * If the last property state is not a closure, it will override everything before it.
         * Therefore, we just take the last state and use it as value for that property.
         *
         *  $collapsedState = [
         *      'id' => [1, 2],
         *      'key' => ['a', fn ($attrs) => $attrs['key'] . 'b' . $attrs['id']],
         *      'prop' => [OtherClassFactory::new(), fn ($attrs) => $attrs['prop']->someState()],
         *      'action' => [ClosureValue::of(fn () => 123)],
         *  ];
         * turns into
         *  $collapsedState = [
         *      'id' => 2,
         *      'key' => ['a', fn ($attrs) => $attrs['key'] . 'b' . $attrs['id']],
         *      'prop' => [OtherClassFactory::new(), fn ($attrs) => $attrs['prop']->someState()],
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         *
         * This way we collapse everything we can before evaluating closures, so they get the most up-to-date state
         * as first argument.
         */
        $collapsedState = array_map(
            fn ($state) => end($state) instanceof Closure ? $state : end($state),
            $collapsedState,
        );

        /**
         * Collapse state for the remaining properties that have the last state as closure.
         *
         *  $collapsedState = [
         *      'id' => 2,
         *      'key' => ['a', fn ($attrs) => $attrs['key'] . 'b' . $attrs['id']],
         *      'prop' => [OtherClassFactory::new(), fn ($attrs) => $attrs['prop']->someState()],
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         * turns into
         *  $collapsedState = [
         *      'id' => 2,
         *      'key' => 'ab2',
         *      'prop' => OtherClassFactory::new()->someState(),
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         */
        foreach ($collapsedState as $key => $values) {
            if (is_array($values) && end($values) instanceof Closure) {
                $collapsedState[$key] = array_reduce(
                    $values,
                    fn ($carry, $value) => $value instanceof Closure ? $value([$key => $carry] + $collapsedState) : $value,
                );
            }
        }

        /**
         * Make any other pending class factories.
         *
         *  $collapsedState = [
         *      'id' => 2,
         *      'key' => 'ab2',
         *      'prop' => OtherClassFactory::new()->someState(),
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         * turns into
         *  $collapsedState = [
         *      'id' => 2,
         *      'key' => 'ab2',
         *      'prop' => OtherClassFactory::new()->someState()->make(),
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         */
        $collapsedState = array_map($this->makeIfFactory(...), $collapsedState);

        /**
         * Unwrap closure values.
         *
         *  $collapsedState = [
         *      'id' => 2,
         *      'key' => 'ab2',
         *      'prop' => OtherClassFactory::new()->someState()->make(),
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         * turns into
         *  $collapsedState = [
         *      'id' => 2,
         *      'key' => 'ab2',
         *      'prop' => OtherClassFactory::new()->someState()->make(),
         *      'action' => fn () => 123,
         *  ];
         */
        $collapsedState = array_map(
            fn ($value) => $value instanceof ClosureValue ? $value->value : $value,
            $collapsedState,
        );

        return array_intersect_key($collapsedState, $definedProperties);
    }

    private function makeIfFactory(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->make();
        }

        if (is_array($value)) {
            return array_map($this->makeIfFactory(...), $value);
        }

        if (class_exists(Collection::class) && $value instanceof Collection) {
            return $value->map($this->makeIfFactory(...));
        }

        return $value;
    }
}
