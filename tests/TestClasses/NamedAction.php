<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

use Closure;

class NamedAction
{
    public function __construct(
        public readonly string $name,
        public readonly Closure $action,
    ) {
    }
}
