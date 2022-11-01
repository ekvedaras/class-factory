<?php

namespace EKvedaras\ClassFactory;

use Closure;

/** @internal */
final class ClosureState
{
    public function __construct(public readonly Closure $state)
    {
    }
}
