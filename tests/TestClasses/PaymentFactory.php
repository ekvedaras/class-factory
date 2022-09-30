<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

use EKvedaras\ClassFactory\ClassFactory;

/** @extends ClassFactory<Payment> */
class PaymentFactory extends ClassFactory
{
    protected string $class = Payment::class;

    protected function definition(): array
    {
        return [
            'transactionId' => '123',
            'handler' => new PaymentHandler(),
        ];
    }
}
