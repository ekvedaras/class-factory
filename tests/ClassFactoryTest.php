<?php

use EKvedaras\ClassFactory\Tests\TestClasses\Account;
use EKvedaras\ClassFactory\Tests\TestClasses\AccountFactory;
use EKvedaras\ClassFactory\Tests\TestClasses\CustomerFactory;
use EKvedaras\ClassFactory\Tests\TestClasses\ItemFactoryWithClosureInDefinition;
use EKvedaras\ClassFactory\Tests\TestClasses\NameActionFactory;
use EKvedaras\ClassFactory\Tests\TestClasses\OrderFactory;
use EKvedaras\ClassFactory\Tests\TestClasses\PaymentFactory;
use EKvedaras\ClassFactory\Tests\TestClasses\PaymentHandler;
use Illuminate\Support\Collection;

it('can create a class from base definition', function (): void {
    $account = AccountFactory::new()->make();

    expect($account->id)->toBe(1)
        ->and($account->name)->toBe('John Doe');
});

it('can create a class by overriding values via make method', function (): void {
    $account = AccountFactory::new()->make(['id' => 2, 'name' => 'John Smith']);

    expect($account->id)->toBe(2)
        ->and($account->name)->toBe('John Smith');
});

it('can create a class by overriding values via state method', function (): void {
    $account = AccountFactory::new()->state(['id' => 2, 'name' => 'John Smith'])->make();

    expect($account->id)->toBe(2)
        ->and($account->name)->toBe('John Smith');
});

it('can create a class by using predefined state', function (): void {
    $account = AccountFactory::new()->johnSmith()->make();

    expect($account->id)->toBe(2)
        ->and($account->name)->toBe('John Smith');
});

it('can override predefined state', function (): void {
    $account = AccountFactory::new()->johnSmith()->make(['id' => 3]);

    expect($account->id)->toBe(3)
        ->and($account->name)->toBe('John Smith');
});

it('evaluates states that are callbacks', function (): void {
    $account = AccountFactory::new()->make(['id' => fn () => 4]);

    expect($account->id)->toBe(4)
        ->and($account->name)->toBe('John Doe');
});

it('passes current attributes to state callbacks', function (): void {
    $account = AccountFactory::new()->state(['id' => function (array $attributes) {
        expect($attributes)->toBe(['id' => 1, 'name' => 'John Doe']);

        return 2;
    }])->make(['id' => function (array $attributes) {
        expect($attributes)->toBe(['id' => 2, 'name' => 'John Doe']);

        return 3;
    }]);

    expect($account->id)->toBe(3);
});

it('automatically makes other factories as properties', function (): void {
    $customer = CustomerFactory::new()->make();

    expect($customer->primaryAccount)->toBeInstanceOf(Account::class);
});

it('automatically makes other factories inside array properties', function (): void {
    $customer = CustomerFactory::new()->make();

    expect($customer->linkedAccounts[1])->toBeInstanceOf(Account::class)
        ->and($customer->linkedAccounts[2])
        ->toBeInstanceOf(Account::class);
});

it('automatically makes other factories inside collection properties', function (): void {
    $order = OrderFactory::new()->make();

    expect($order->items->first()->id)->toBe(1)
        ->and($order->items->first()->price)->toBe(100_50)
        ->and($order->items->last()->id)->toBe(2)
        ->and($order->items->last()->price)->toBe(200_25);
})->skip(! class_exists(Collection::class));

it('can modify the class after making it', function (): void {
    $account = AccountFactory::new()->after(function (Account $account): void {
        $account->name = 'Modified John';
    })->make();

    expect($account->name)->toBe('Modified John');
});

it('can create factories with closures in definition', function (): void {
    $item = ItemFactoryWithClosureInDefinition::new()->make();

    expect($item->price)->toBe($item->id * 10);
});

it('can create factories that have invokable classes as their params', function (): void {
    $payment = PaymentFactory::new()->make();

    expect($payment->handler)->toBeInstanceOf(PaymentHandler::class);
});

it('unwraps closure values', function (): void {
    $namedAction = NameActionFactory::new()->make();

    expect($namedAction->action)->toBeInstanceOf(Closure::class)
        ->and(call_user_func($namedAction->action))->toBe(30);
});

it('ignores properties that don\'t exist', function (): void {
    $account = AccountFactory::new()->make(['nonExistingProperty' => 1]);

    expect($account)->toBeInstanceOf(Account::class)
        ->and($account)->not()->toHaveProperty('nonExistingProperty');
});
