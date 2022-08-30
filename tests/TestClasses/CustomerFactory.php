<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

use EKvedaras\ClassFactory\ClassFactory;

/** @extends ClassFactory<Customer> */
class CustomerFactory extends ClassFactory
{
    protected string $class = Customer::class;

    protected function definition(): array
    {
        return [
            'id' => 1,
            'linkedAccounts' => [
                1 => AccountFactory::new()->state(['id' => 1]),
                2 => AccountFactory::new()->make(['id' => 2]),
            ],
            'primaryAccount' => AccountFactory::new(),
        ];
    }
}
