<?php

namespace EKvedaras\ClassFactory;

use Closure;

class ClosureValue
{
    public function __construct(public readonly Closure $value)
    {
    }

    public static function of(Closure $value): self
    {
        return new self($value);
    }
}
