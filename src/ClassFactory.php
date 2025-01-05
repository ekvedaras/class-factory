<?php

namespace EKvedaras\ClassFactory;

use Closure;
use Illuminate\Support\Collection;

/**
 * @template T of object
 */
abstract class ClassFactory
{
    /** @var class-string<T> */
    protected string $class;

    /** @var array<int, array<string, mixed>|Closure> */
    private array $states = [];

    /** @var Closure[] */
    private array $lateTransformers = [];

    final public function __construct()
    {
    }

    public static function new(): static
    {
        $factory = new static();

        return $factory->state($factory->definition());
    }

    /** @return array<string, mixed> */
    abstract protected function definition(): array;

    /** @param array<string, mixed>|Closure $state */
    public function state(array | Closure $state): static
    {
        $this->states[] = $state;

        return $this;
    }

    public function after(Closure $transformer): static
    {
        $this->lateTransformers[] = $transformer;

        return $this;
    }

    /**
     * @param array<string, mixed>|Closure|null $state
     * @return T
     */
    public function make(array | Closure | null $state = null): object
    {
        if (isset($state)) {
            $this->state($state);
        }

        $object = $this->newInstance($this->collapseStates());

        foreach ($this->lateTransformers as $transformer) {
            $transformer($object);
        }

        return $object;
    }

    /**
     * @param array<string, mixed> $properties
     * @return T
     */
    protected function newInstance(array $properties): object
    {
        return new $this->class(...$properties);
    }

    /** @return array<string, mixed> */
    private function collapseStates(): array
    {
        $definition = $this->definition();
        $definedProperties = array_flip(array_keys($definition));

        $collapsedStateWithPendingFactories = array_reduce(
            $this->states,
            function (array $collapsedState, array | Closure $stateWithPendingClosures) {
                $stateWithResolvedClosures = array_map(
                    fn ($value) => $value instanceof Closure ? $value($collapsedState) : $value,
                    $stateWithPendingClosures instanceof Closure ? $stateWithPendingClosures($collapsedState) : $stateWithPendingClosures,
                );

                return array_merge($collapsedState, $stateWithResolvedClosures);
            },
            initial: $definition,
        );

        $collapsedStateWithMadeFactories = array_map(
            $this->makeIfFactory(...),
            $collapsedStateWithPendingFactories,
        );

        $collapsedStateWithUnwrappedValues = array_map(
            fn ($value) => $value instanceof ClosureValue ? $value->value : $value,
            $collapsedStateWithMadeFactories,
        );

        return array_intersect_key($collapsedStateWithUnwrappedValues, $definedProperties);
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
