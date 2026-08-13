# rasuvaeff/property-testing-names

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/property-testing-names/v)](https://packagist.org/packages/rasuvaeff/property-testing-names)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/property-testing-names/downloads)](https://packagist.org/packages/rasuvaeff/property-testing-names)
[![Build](https://github.com/rasuvaeff/property-testing-names/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/property-testing-names/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/property-testing-names/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/property-testing-names/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/property-testing-names/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/property-testing-names/php)](https://packagist.org/packages/rasuvaeff/property-testing-names)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)

[Русская версия](README.ru.md)

Person-name generators for the
[property-testing engine](https://github.com/rasuvaeff/property-testing-core):
given names, surnames and patronymics for `en` and `ru`, with grammatical
gender kept consistent across the parts of one name. Names shrink toward the
shortest entries of their dataset, so a counterexample minimises into a plain
name instead of turning into random text.

> Using an AI coding assistant? [llms.txt](llms.txt) contains a compact API reference you can share with the model.

## Why a separate package

The core `Gen` facade holds format-derived primitives — `Gen::email()`,
`Gen::uuid()`, `Gen::ipv4()` — things a formula produces. Name lists are
versioned data with their own update policy, so they live here, behind their
own `Names::` facade. There is no `Gen::name()`: a core method that silently
depends on an optional data package would pass `composer-require-checker` in
your project and fail at runtime.

| Package | Use it when |
|---|---|
| [`rasuvaeff/property-testing-core`](https://github.com/rasuvaeff/property-testing-core) | The engine itself: arbitraries, shrinking, corpus |
| [`rasuvaeff/property-testing-testo`](https://github.com/rasuvaeff/property-testing-testo) | You test with Testo — the `#[Property]` attribute |
| [`rasuvaeff/property-testing-phpunit`](https://github.com/rasuvaeff/property-testing-phpunit) | You test with PHPUnit — the `forAll()->check()` trait |
| **`rasuvaeff/property-testing-names`** (this package) | Your inputs are people: forms, profiles, auth, validators, reports |

## Requirements

- PHP 8.3 – 8.5
- `ext-mbstring`
- `rasuvaeff/property-testing-core` `^0.1 || ^0.2`

## Installation

```bash
composer require --dev rasuvaeff/property-testing-names
```

## Usage

```php
use Rasuvaeff\PropertyTesting\Names\Gender;
use Rasuvaeff\PropertyTesting\Names\Names;

Names::first();                          // 'Ian', 'Emma', …
Names::last(locale: 'ru');               // 'Попов', 'Иванова', …
Names::first('ru', Gender::Female);      // 'Мария', 'Ольга', …
Names::middle('ru');                     // 'Ивановна', 'Петрович', …
Names::full('ru', middle: true);         // 'Иван Иванович Иванов'
Names::person('ru', middle: true);       // a PersonName value object
```

Inside a property test the factories go into the generators method, exactly
like the core ones:

```php
use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Names\Names;
use Rasuvaeff\PropertyTesting\Property;
use Testo\Assert;

#[Property(runs: 300)]
public function displayNameFitsTheColumn(string $first, string $last): void
{
    Assert::true(mb_strlen((new Profile($first, $last))->displayName()) <= 255);
}

/** @return array<string, ArbitraryInterface> */
public static function displayNameFitsTheColumnGenerators(): array
{
    return [
        'first' => Names::first(locale: 'ru'),
        'last' => Names::last(locale: 'ru'),
    ];
}
```

### API

| Factory | Returns | Notes |
|---|---|---|
| `Names::first(string $locale = 'en', ?Gender $gender = null)` | `ArbitraryInterface<non-empty-string>` | Without a gender the male and female lists are merged |
| `Names::last(string $locale = 'en', ?Gender $gender = null)` | `ArbitraryInterface<non-empty-string>` | Inflected per gender where the locale requires it |
| `Names::middle(string $locale, ?Gender $gender = null)` | `ArbitraryInterface<non-empty-string>` | Patronymics; the locale is **required** because the dataset is not universal |
| `Names::full(string $locale = 'en', ?Gender $gender = null, bool $middle = false)` | `ArbitraryInterface<non-empty-string>` | `First [Middle] Last`, rendered from `person()` |
| `Names::person(string $locale = 'en', ?Gender $gender = null, bool $middle = false)` | `ArbitraryInterface<PersonName>` | The parts, kept together |

`PersonName` is a `final readonly class` with `$first`, `$middle` (nullable),
`$last`, `$gender` and three display forms:

| Method | `en` | `ru` |
|---|---|---|
| `full()` | `John Smith` | `Иван Иванович Иванов` |
| `initialLast()` | `J. Smith` | `И. Иванов` |
| `lastInitials()` | `Smith J.` | `Иванов И. И.` |

Any other form is one `Gen::map()` away:

```php
Gen::map(Names::person(), static fn (PersonName $p): string => $p->last . ', ' . $p->first);
```

### Locales

| Locale | Given names | Surnames | Patronymics |
|---|---|---|---|
| `en` | 50 male + 50 female | 100, shared by both genders | — |
| `ru` | 50 male + 50 female | 50 + 50, index-aligned pairs | 40 + 40, index-aligned pairs |

An unregistered locale raises `InvalidArgumentException` when the arbitrary is
**built**, not when it first generates a value; the same is true for asking
`en` for middle names. Locale tags are matched literally: `'EN'`, `'en-US'` and
`'en '` are all unknown.

### Gender consistency

`Names::first()` and `Names::last()` are independent draws — combining them by
hand can produce `Мария Иванов`, which no Russian form renders. When the parts
must agree, draw them together:

```php
$person = Names::person('ru', middle: true);   // Мария Ивановна Иванова
```

`Gender` has two cases, `Male` and `Female`, declared in that order because
shrinking walks toward the first case.

## Security

The lists are synthetic test data: they are not a register of real people and
make no claim of cultural completeness. Generated values are printable UTF-8
without control characters, so they are safe to embed in test reports and
failure messages — but they are still generated input, and code under test
should validate them like any other user data.

Dataset changes alter the values a given seed produces, so they ship as
**minor** releases and are listed in [CHANGELOG.md](CHANGELOG.md).

## Examples

Runnable scripts live in [`examples/`](examples/README.md).

## Development

```bash
make build          # validate + normalize + require-checker + cs + psalm + test
make cs-fix
make psalm
make test
make test-coverage
make mutation
make release-check
```

No PHP on the host is required — every target runs in the `composer:2` Docker
image.

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
