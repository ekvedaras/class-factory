<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

class Customer
{
    /** @param Account[] $linkedAccounts */
    public function __construct(
        public readonly int $id,
        public readonly array $linkedAccounts,
        public readonly Account $primaryAccount,
    ) {
    }
}
