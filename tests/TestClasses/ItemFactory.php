<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

use EKvedaras\ClassFactory\ClassFactory;

/** @extends ClassFactory<Item> */
class ItemFactory extends ClassFactory
{
    protected string $class = Item::class;

    protected function definition(): array
    {
        return [
            'id' => 1,
            'price' => 10,
        ];
    }

    public function pricedAt(float $price): static
    {
        return $this->state(['price' => (int) round($price * 100)]);
    }
}
