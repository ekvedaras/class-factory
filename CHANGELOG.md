# Changelog

All notable changes to `class-factory` will be documented in this file.

## v1.2.0 - 2023-02-15

### Added

- Ability to customize how the class is created via `newInstance`. @erikgaal

## v1.1.5 - 2023-01-16

- Add phpstan with level 9
- Start testing with php 8.2

## v1.1.4 - 2023-01-12

Make phpstan happier ❤️

## v1.1.3 - 2022-11-02

### Fixed

- Revert to old behaviour of resolving closure states as it breaks more things than it solves.

## v1.1.2 - 2022-11-01

### Fixed

- Handling of non-existing properties while collapsing states.

## v1.1.1 - 2022-11-01

### Fixed

- Unwrap closure states after collapsing properties without property closure states and before collapsing property closure states.

## v1.1.0 - 2022-11-01

### Added

- `ClosureValue` to wrap closures that should be passed to class constructor as plain closures will be evaluated before doing so.

### Fixed

- Make sure property closure states get the most up-to-date attributes.

## v1.0.2 - 2022-09-30

### Fixed

- Do not cal invokable classes when collapsing states. Check for closures instead of callables.

## v1.0.1 - 2022-09-03

### Fixed

- Make sure closures in definition get filled attributes array.

### Updated

- Improve clarity in `collapseStates()` method.

## v1.0.0 - 2022-08-30

🏭
