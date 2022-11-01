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

    /** @var array[]|Closure[]|ClosureState[] */
    private array $states = [];

    /** @var Closure[] */
    private array $lateTransformers = [];

    /** @return static<T> */
    public static function new(): static
    {
        return new static();
    }

    abstract protected function definition(): array;

    /** @return static<T> */
    public function state(array | Closure $state): static
    {
        $this->states[] = $state instanceof Closure ? new ClosureState($state) : $state;

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

        /** @var ClosureState[] $closureStates */
        $closureStates = [];

        /**
         * Evaluate states that are closures and group all resulting states per property
         *
         *  $this->states = [
         *      ['id' => 1, 'key' => 'a', 'prop' => OtherClassFactory::new(), 'action' = ClosureValue::of(fn () => 123)],
         *      ['id' => 2],
         *      new ClosureState(fn ($attrs) => ['key' => $attrs['key'] . 'b'])
         *      ['key' => fn ($attrs) => $attrs['key'] . $attrs['id']],
         *      ['prop' => fn ($attrs) => $attrs['prop']->someState()],
         *  ];
         * turns into
         *  $collapsedState = [
         *      'id' => new CollapsingState([1, 2]),
         *      'key' => new CollapsingState(['a', fn ($attrs) => $attrs['key'] . $attrs['id']]),
         *      'prop' => new CollapsingState([OtherClassFactory::new(), fn ($attrs) => $attrs['prop']->someState()]),
         *      'action' => new CollapsingState([ClosureValue::of(fn () => 123)]),
         *  ];
         *  $closureStates = [new ClosureState(fn ($attrs) => ['id' => 3, 'key' => $attrs['key'] . 'b'])];
         */
        $collapsedState = array_reduce(
            $this->states,
            function (array $carry, array | Closure | ClosureState $state) use (&$closureStates) {
                /** @var array<string, CollapsingState> $carry */

                if ($state instanceof ClosureState) {
                    $closureStates[] = $state;

                    return $carry;
                }

                foreach ($state as $key => $value) {
                    ($carry[$key] ?? null)?->add($value);
                }

                return $carry;
            },
            initial: array_map(fn ($value) => new CollapsingState([$value]), $this->definition()),
        );

        /**
         * If the last property state is not a closure or closure state, it will override everything before it.
         * Therefore, we just take the last state and use it as value for that property.
         *
         *  $collapsedState = [
         *      'id' => new CollapsingState([1, 2]),
         *      'key' => new CollapsingState(['a', fn ($attrs) => $attrs['key'] . $attrs['id']]),
         *      'prop' => new CollapsingState([OtherClassFactory::new(), fn ($attrs) => $attrs['prop']->someState()]),
         *      'action' => new CollapsingState([ClosureValue::of(fn () => 123)]),
         *  ];
         *  $closureStates = [new ClosureState(fn ($attrs) => ['id' => 3, 'key' => $attrs['key'] . 'b'])];
         * turns into
         *  $collapsedState = [
         *      'id' => 2,
         *      'key' => new CollapsingState(['a', fn ($attrs) => $attrs['key'] . $attrs['id']]),
         *      'prop' => new CollapsingState([OtherClassFactory::new(), fn ($attrs) => $attrs['prop']->someState()]),
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         *  $closureStates = [new ClosureState(fn ($attrs) => ['id' => 3, 'key' => $attrs['key'] . 'b'])];
         *
         * This way we collapse everything we can before evaluating closures, so they get the most up-to-date state
         * as first argument.
         */
        $collapsedState = array_map(
            fn (CollapsingState $state) => $state->endsWithClosure() ? $state : $state->last(),
            $collapsedState,
        );

        /**
         * Unwrap closure states.
         *
         *  $collapsedState = [
         *      'id' => 2,
         *      'key' => new CollapsingState(['a', fn ($attrs) => $attrs['key'] . $attrs['id']]),
         *      'prop' => new CollapsingState([OtherClassFactory::new(), fn ($attrs) => $attrs['prop']->someState()]),
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         *  $closureStates = [new ClosureState(fn ($attrs) => ['id' => 3, 'key' => $attrs['key'] . 'b'])];
         * turns into
         *  $collapsedState = [
         *      'id' => 3,
         *      'key' => new CollapsingState(['a', fn ($attrs) => $attrs['key'] . $attrs['id'], fn ($attrs) => $attrs['key'] . 'b']),
         *      'prop' => new CollapsingState([OtherClassFactory::new(), fn ($attrs) => $attrs['prop']->someState()]),
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         */
        foreach ($closureStates as $closureState) {
            foreach (call_user_func($closureState->state, $collapsedState) as $key => $value) {
                if (! array_key_exists($key, $collapsedState)) {
                    continue;
                }

                if ($collapsedState[$key] instanceof CollapsingState) {
                    $collapsedState[$key]->add($value);

                    continue;
                }

                if ($value instanceof Closure) {
                    $collapsedState[$key] = new CollapsingState([$collapsedState[$key], $value]);

                    continue;
                }

                $collapsedState[$key] = $value;
            }
        }

        /**
         * Collapse state for the remaining properties that have the last state as closure.
         *
         *  $collapsedState = [
         *      'id' => 3,
         *      'key' => new CollapsingState(['a', fn ($attrs) => $attrs['key'] . $attrs['id'], fn ($attrs) => $attrs['key'] . 'b']),
         *      'prop' => new CollapsingState([OtherClassFactory::new(), fn ($attrs) => $attrs['prop']->someState()]),
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         * turns into
         *  $collapsedState = [
         *      'id' => 3,
         *      'key' => 'a2b',
         *      'prop' => OtherClassFactory::new()->someState(),
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         */
        foreach ($collapsedState as $key => $propertyState) {
            if ($propertyState instanceof CollapsingState) {
                $collapsedState[$key] = array_reduce(
                    $propertyState->states(),
                    fn ($carry, $value) => $value instanceof Closure ? $value([$key => $carry] + $collapsedState) : $value,
                );
            }
        }

        /**
         * Make any other pending class factories.
         *
         *  $collapsedState = [
         *      'id' => 3,
         *      'key' => 'a2b',
         *      'prop' => OtherClassFactory::new()->someState(),
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         * turns into
         *  $collapsedState = [
         *      'id' => 3,
         *      'key' => 'a2b',
         *      'prop' => OtherClassFactory::new()->someState()->make(),
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         */
        $collapsedState = array_map($this->makeIfFactory(...), $collapsedState);

        /**
         * Unwrap closure values.
         *
         *  $collapsedState = [
         *      'id' => 3,
         *      'key' => 'a2b',
         *      'prop' => OtherClassFactory::new()->someState()->make(),
         *      'action' => ClosureValue::of(fn () => 123),
         *  ];
         * turns into
         *  $collapsedState = [
         *      'id' => 3,
         *      'key' => 'a2b',
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
