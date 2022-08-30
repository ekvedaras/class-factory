# Factory for creating objects via constructor arguments

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ekvedaras/class-factory.svg?style=flat-square)](https://packagist.org/packages/ekvedaras/class-factory)
[![Tests](https://github.com/ekvedaras/class-factory/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/ekvedaras/class-factory/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/ekvedaras/class-factory.svg?style=flat-square)](https://packagist.org/packages/ekvedaras/class-factory)

A factory class that uses passes each property directly to constructor.
This way your class does not need to deal received array create itself from and there is no reflection magic involved.
This is mostly useful for creating plain classes like value objects, entities, DTOs, etc.

## Installation

You can install the package via composer:

```bash
composer require ekvedaras/class-factory
```

## PhpStorm plugin

⏳ Coming soon...

## Usage

```php
use EKvedaras\ClassFactory\ClassFactory;

class Account {
    public function __construct(
        public readonly int $id,
        public string $name,
        public array $orders,
    ) {
    }
}

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
    ->johnSmith() // Can use predefiened states
    ->state(['name' => 'John Smitgh Jnr.']) // Can override factory state on the fly
    ->after(fn (Account $account) => sort($account->orders)) // Can modify constructed object after it was created
    ->make(['id' => 3]) // Can provide final modifications and return the new object
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
