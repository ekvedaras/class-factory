<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

use EKvedaras\ClassFactory\ClassFactory;

/** @extends ClassFactory<Account> */
class AccountFactory extends ClassFactory
{
    protected string $class = Account::class;

    protected function definition(): array
    {
        return [
            'id' => 1,
            'name' => 'John Doe',
        ];
    }

    public function johnSmith(): static
    {
        return $this->state([
            'id' => 2,
            'name' => 'John Smith',
        ]);
    }
}
