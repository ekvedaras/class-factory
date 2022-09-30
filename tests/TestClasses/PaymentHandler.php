<?php

namespace EKvedaras\ClassFactory\Tests\TestClasses;

class PaymentHandler
{
    public function __invoke(): string
    {
        return '42';
    }
}
