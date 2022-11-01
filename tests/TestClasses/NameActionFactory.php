<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

use EKvedaras\ClassFactory\ClassFactory;
use EKvedaras\ClassFactory\ClosureValue;

/** @extends ClassFactory<NamedAction> */
class NameActionFactory extends ClassFactory
{
    protected string $class = NamedAction::class;

    protected function definition(): array
    {
        return [
            'name' => 'calculate-price',
            'action' => ClosureValue::of(fn () => 30),
        ];
    }
}
