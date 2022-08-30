<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

class Account
{
    public function __construct(
        public readonly int $id,
        public string $name,
    ) {
    }
}
