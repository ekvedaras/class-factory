<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

use EKvedaras\ClassFactory\ClassFactory;

/** @extends ClassFactory<Order> */
class OrderFactory extends ClassFactory
{
    protected string $class = Order::class;

    protected function definition(): array
    {
        return [
            'id' => 1,
            'items' => collect([
                ItemFactory::new()->pricedAt(100.5)->make(['id' => 1]),
                ItemFactory::new()->pricedAt(200.25)->make(['id' => 2]),
            ]),
        ];
    }
}
