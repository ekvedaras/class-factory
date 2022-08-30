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
        return array_intersect_key(
            array_map(
                $this->makeIfFactory(...),
                array_reduce(
                    $this->states,
                    fn (array $collapsedState, array | Closure $state) => array_merge(
                        $collapsedState,
                        array_map(
                            fn ($value) => is_callable($value) ? $value($collapsedState) : $value,
                            is_callable($state) ? $state($collapsedState) : $state,
                        ),
                    ),
                    [],
                )
            ),
            array_flip(array_keys($this->definition()))
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

        if (class_exists(Collection::class) && $value instanceof Collection) {
            return $value->map($this->makeIfFactory(...));
        }

        return $value;
    }
}
