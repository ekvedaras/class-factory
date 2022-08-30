<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

class Item
{
    public function __construct(
        public readonly int $id,
        public readonly int $price,
    ) {
    }
}
