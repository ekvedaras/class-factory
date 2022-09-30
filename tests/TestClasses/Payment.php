<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

class Payment
{
    public function __construct(
        public readonly string $transactionId,
        public readonly PaymentHandler $handler,
    ) {
    }
}
