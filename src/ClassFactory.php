<?php

namespace EKvedaras\ClassFactory;

use Closure;
use Illuminate\Support\Arr;
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
        return tap(new static(), fn (self $factory) => $factory->state($factory->definition()));
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

        return tap(
            new $this->class(...$this->collapseStates()),
            function ($object) {
                foreach ($this->lateTransformers as $transformer) {
                    $transformer($object);
                }
            }
        );
    }

    private function collapseStates(): array
    {
        return Arr::only(
            array_map(
                $this->makeIfFactory(...),
                array_reduce(
                    $this->states,
                    fn (array $collapsedState, array | Closure $state) => array_merge(
                        $collapsedState,
                        array_map(
                            fn ($value) => value($value, $collapsedState),
                            value($state, $collapsedState),
                        ),
                    ),
                    [],
                )
            ),
            array_keys($this->definition()),
        );
    }

    private function makeIfFactory(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->make();
        }

        if (is_array($value)) {
            return array_map($this->makeIfFactory(...), $value);
        }

        if ($value instanceof Collection) {
            return $value->map($this->makeIfFactory(...));
        }

        return $value;
    }
}
