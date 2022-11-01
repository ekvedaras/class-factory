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

it('can create a class from base definition', function () {
    $account = AccountFactory::new()->make();

    expect($account->id)->toBe(1)
        ->and($account->name)->toBe('John Doe');
});

it('can create a class by overriding values via make method', function () {
    $account = AccountFactory::new()->make(['id' => 2, 'name' => 'John Smith']);

    expect($account->id)->toBe(2)
        ->and($account->name)->toBe('John Smith');
});

it('can create a class by overriding values via state method', function () {
    $account = AccountFactory::new()->state(['id' => 2, 'name' => 'John Smith'])->make();

    expect($account->id)->toBe(2)
        ->and($account->name)->toBe('John Smith');
});

it('can create a class by using predefined state', function () {
    $account = AccountFactory::new()->johnSmith()->make();

    expect($account->id)->toBe(2)
        ->and($account->name)->toBe('John Smith');
});

it('can override predefined state', function () {
    $account = AccountFactory::new()->johnSmith()->make(['id' => 3]);

    expect($account->id)->toBe(3)
        ->and($account->name)->toBe('John Smith');
});

it('evaluates states that are callbacks', function () {
    $account = AccountFactory::new()->make(['id' => fn () => 4]);

    expect($account->id)->toBe(4)
        ->and($account->name)->toBe('John Doe');
});

it('passes current attributes to state callbacks', function () {
    $account = AccountFactory::new()->state(['id' => function (array $attributes) {
        expect($attributes)->toBe(['id' => 1, 'name' => 'John Doe']);

        return 2;
    }])->make(['id' => function (array $attributes) {
        expect($attributes)->toBe(['id' => 2, 'name' => 'John Doe']);

        return 3;
    }]);

    expect($account->id)->toBe(3);
});

it('automatically makes other factories as properties', function () {
    $customer = CustomerFactory::new()->make();

    expect($customer->primaryAccount)->toBeInstanceOf(Account::class);
});

it('automatically makes other factories inside array properties', function () {
    $customer = CustomerFactory::new()->make();

    expect($customer->linkedAccounts[1])->toBeInstanceOf(Account::class)
        ->and($customer->linkedAccounts[2])
        ->toBeInstanceOf(Account::class);
});

it('automatically makes other factories inside collection properties', function () {
    $order = OrderFactory::new()->make();

    expect($order->items->first()->id)->toBe(1)
        ->and($order->items->first()->price)->toBe(100_50)
        ->and($order->items->last()->id)->toBe(2)
        ->and($order->items->last()->price)->toBe(200_25);
})->skip(! class_exists(Collection::class));

it('can modify the class after making it', function () {
    $account = AccountFactory::new()->after(function (Account $account) {
        $account->name = 'Modified John';
    })->make();

    expect($account->name)->toBe('Modified John');
});

it('can create factories with closures in definition', function () {
    $item = ItemFactoryWithClosureInDefinition::new()->make();

    expect($item->price)->toBe($item->id * 10);
});

it('can create factories that have invokable classes as their params', function () {
    $payment = PaymentFactory::new()->make();

    expect($payment->handler)->toBeInstanceOf(PaymentHandler::class);
});

it('resolves closures only after collapsing states', function () {
    $item = ItemFactoryWithClosureInDefinition::new()->make(['id' => 5]);

    expect($item->price)->toBe(50);
});

it('unwraps closure values', function () {
    $namedAction = NameActionFactory::new()->make();

    expect($namedAction->action)->toBeInstanceOf(Closure::class)
        ->and(call_user_func($namedAction->action))->toBe(30);
});
