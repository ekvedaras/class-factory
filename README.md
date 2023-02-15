# Factory for creating objects via constructor arguments

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ekvedaras/class-factory.svg?style=flat-square)](https://packagist.org/packages/ekvedaras/class-factory)
[![Tests](https://github.com/ekvedaras/class-factory/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/ekvedaras/class-factory/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/ekvedaras/class-factory.svg?style=flat-square)](https://packagist.org/packages/ekvedaras/class-factory)

A factory class that passes each property directly to constructor.
This way your class does not need to deal with received array to create itself from and there is no reflection magic involved.
This is mostly useful for creating plain classes like value objects, entities, DTOs, etc.

## Installation

You can install the package via composer:

```bash
composer require --dev ekvedaras/class-factory
```

## PhpStorm plugin

Provides autocomplete and refactoring capabillities for PhpStorm.

* Plugin: [ClassFactory](https://plugins.jetbrains.com/plugin/19824-classfactory)
* Repository: [class-factory-phpstorm](https://github.com/ekvedaras/class-factory-phpstorm)

## Usage

```php
use EKvedaras\ClassFactory\ClassFactory;
use EKvedaras\ClassFactory\ClosureValue;

class Account {
    public function __construct(
        public readonly int $id,
        public string $name,
        public array $orders,
        public \Closure $monitor,
    ) {
    }
}

/** @extends ClassFactory<Account> */
class AccountFactory extends ClassFactory {
    protected string $class = Account::class;
    
    protected function definition(): array
    {
        return [
            'id' => 1,
            'name' => 'John Doe',
            'orders' => [
                OrderFactory::new()->state(['id' => 1]),
                OrderFactory::new()->state(['id' => 2]),
            ],
            'monitor' => new ClosureValue(fn () => true),
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

$account = AccountFactory::new()
    ->johnSmith()                                                           // Can use predefiened states
    ->state(['name' => 'John Smitgh Jnr'])                                  // Can override factory state on the fly
    ->state(['name' => fn (array $attributes) => "{$attributes['name']}."]) // Can use closures and have access to already defined attributes
    ->after(fn (Account $account) => sort($account->orders))                // Can modify constructed object after it was created
    ->state(['monitor' => new ClosureValue(fn () => false)])                // Can set state of closure type properties using `ClosureValue` wrapper
    ->make(['id' => 3])                                                     // Can provide final modifications and return the new object
```

### Customising class creation

If you don't want class to be created by directly passing attributes to constructor, you can override `newInstance` method in the factory and do change the behavior.

```php
protected function newInstance(array $properties): object
{
    return Account::makeUsingProperties($properties);
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ernestas Kvedaras](https://github.com/ekvedaras)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
