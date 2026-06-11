# Map requests using Valinor

* Status: Accepted
* Date: 2026-05-14

## Context and problem statement

Shlink has traditionally been using the `laminas/laminas-inputfilter` package for input filtering and validation, and then mapped DTOs manually from the result of InputFilter objects.

This package (together with `laminas/laminas-filter` and `laminas/laminas-validation`) has a few problems:

* Is starting to be left behind, blocking the update to `laminas/laminas-servicemanager` 4 for a couple of years already.
* Defining filtering and validation rules is verbose.
* It's not super straightforward to follow when dealing with complex or nested data structures (see [RedirectRulesInputFilter](https://github.com/shlinkio/shlink/blob/f3f351afe56b31baaf4124caa31b191c44dd620e/module/Core/src/RedirectRule/Model/Validation/RedirectRulesInputFilter.php)).
* Forces some type definitions to be duplicated between validation rules and DTOs (a field is a number, a field is a date with a particular format, etc.)

Because of that, I've been considering using the `cuyz/valinor` package, which allows to map data structures into objects, with implicit validation based on types and PHPStan annotations.

For more complex filtering and validation rules, it supports [custom converters](https://valinor-php.dev/latest/how-to/convert-input/), which can replace existing custom input filters.

## Considered options

1. Keep `laminas/laminas-inputfilter`.
2. Migrate to `cuyz/valinor`.

## Decision outcome

Using `cuyz/valinor` will save many lines of code and make data mapping more straightforward, even with a couple rough edges in mind.

## Pros and Cons of the Options

### 1 - Keep `laminas/laminas-inputfilter`

* Good: because no code changes are needed.
* Good: because we can continue using some custom rules created over the years to work with this package.
* Bad: because it's blocking the update to `laminas/laminas-servicemanager` 4, without a clear path for this to change.
* Bad: because working with it is a bit cumbersome and requires a lot of boilerplate.
* Bad: because it causes type definition duplication between DTO fields and validation rules.

### 2 - Migrate to `cuyz/valinor`

* Good: because it reduces the amount of dependencies and unblocks the update to `laminas/laminas-servicemanager` 4.
* Good: because it reduces duplication and allows to rely on nested DTOs for complex data structures, which are more intuitive to use.
* Bad: because it relies in an external mapper to fully ensure DTOs are valid on creation, since some filtering and validation is defined in the mapper configuration or attributes only applied by the mapper.
* Bad: because it requires refactoring a lot of code and adjusting tests.
* Bad: because many custom filtering and validation rules need to be recreated.
