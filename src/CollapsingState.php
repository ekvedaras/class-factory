<?php

namespace EKvedaras\ClassFactory;

use Closure;
use InvalidArgumentException;

/** @internal */
final class CollapsingState
{
    public function __construct(private array $states = [])
    {
    }

    public function add($state): self
    {
        if ($state instanceof ClosureState) {
            throw new InvalidArgumentException('Closure states need to be dealt with separately.');
        }

        $this->states[] = $state;

        return $this;
    }

    public function endsWithClosure(): bool
    {
        return end($this->states) instanceof Closure;
    }

    public function last(): mixed
    {
        return end($this->states);
    }

    public function states(): array
    {
        return $this->states;
    }
}
