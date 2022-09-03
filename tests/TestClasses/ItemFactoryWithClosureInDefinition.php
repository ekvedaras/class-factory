<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

use EKvedaras\ClassFactory\ClassFactory;

/** @extends ClassFactory<Item> */
class ItemFactoryWithClosureInDefinition extends ClassFactory
{
    protected string $class = Item::class;

    protected function definition(): array
    {
        return [
            'id' => 20,
            'price' => fn (array $attributes) => $attributes['id'] * 10,
        ];
    }
}
