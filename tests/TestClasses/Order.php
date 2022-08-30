<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

use Illuminate\Support\Collection;

class Order
{
    /** @param Collection<array-key, Item> $items */
    public function __construct(
        public readonly int $id,
        public readonly Collection $items,
    ) {
    }
}
