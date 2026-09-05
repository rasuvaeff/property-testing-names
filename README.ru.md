# rasuvaeff/property-testing-names

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/property-testing-names/v)](https://packagist.org/packages/rasuvaeff/property-testing-names)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/property-testing-names/downloads)](https://packagist.org/packages/rasuvaeff/property-testing-names)
[![Build](https://github.com/rasuvaeff/property-testing-names/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/property-testing-names/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/property-testing-names/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/property-testing-names/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/property-testing-names/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/property-testing-names/php)](https://packagist.org/packages/rasuvaeff/property-testing-names)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)

[English version](README.md)

Генераторы имён людей для
[движка property-тестирования](https://github.com/rasuvaeff/property-testing-core):
имена, фамилии и отчества для `en` и `ru`, с согласованным грамматическим родом
между частями одного имени. Значения шринкаются к самым коротким записям
набора, поэтому контрпример сводится к обычному имени, а не превращается в
случайный текст.

> Используете AI-ассистента? [llms.txt](llms.txt) — компактный справочник по API, его можно передать модели.

## Зачем отдельный пакет

Фасад `Gen` в ядре держит примитивы, выводимые из формата — `Gen::email()`,
`Gen::uuid()`, `Gen::ipv4()`. Списки имён — это versioned-данные со своей
политикой обновления, поэтому они живут здесь, за собственным фасадом
`Names::`. Метода `Gen::name()` не существует: метод ядра, тихо зависящий от
необязательного data-пакета, прошёл бы `composer-require-checker` в вашем
проекте и упал бы в рантайме.

| Пакет | Когда нужен |
|---|---|
| [`rasuvaeff/property-testing-core`](https://github.com/rasuvaeff/property-testing-core) | Сам движок: arbitrary, шринкинг, корпус |
| [`rasuvaeff/property-testing-testo`](https://github.com/rasuvaeff/property-testing-testo) | Тесты на Testo — атрибут `#[Property]` |
| [`rasuvaeff/property-testing-phpunit`](https://github.com/rasuvaeff/property-testing-phpunit) | Тесты на PHPUnit — трейт с `forAll()->check()` |
| **`rasuvaeff/property-testing-names`** (этот пакет) | На входе люди: формы, профили, авторизация, валидаторы, отчёты |

## Требования

- PHP 8.3 – 8.5
- `ext-mbstring`
- `rasuvaeff/property-testing-core` `^0.8`

## Установка

```bash
composer require --dev rasuvaeff/property-testing-names
```

## Использование

Каждая фабрика возвращает `ArbitraryInterface` — *рецепт* значений, а не
значение. В комментариях ниже — то, что каждая из них порождает:

```php
use Rasuvaeff\PropertyTesting\Names\Names;
use Rasuvaeff\PropertyTesting\Names\Gender;

$firstNames = Names::first();                     // 'Ian', 'Emma', …
$surnames = Names::last(locale: 'ru');            // 'Попов', 'Иванова', …
$femaleNames = Names::first('ru', Gender::Female); // 'Мария', 'Ольга', …
$patronymics = Names::middle('ru');               // 'Ивановна', 'Петрович', …
$displayNames = Names::full('ru', middle: true);  // 'Иван Иванович Иванов'
$people = Names::person('ru', middle: true);      // объекты PersonName
```

Тянет из них раннер; до этого момента ничего не генерируется. Посмотреть
значения при отладке можно явной выборкой:

```php
Gen::sample($displayNames, 3, 6);   // фиксированный seed → те же три имени каждый раз
```

В property-тесте фабрики уходят в метод-генератор — ровно как у ядра:

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

| Фабрика | Возвращает | Примечание |
|---|---|---|
| `Names::first(string $locale = 'en', ?Gender $gender = null)` | `ArbitraryInterface<non-empty-string>` | Без указания рода мужской и женский списки объединяются |
| `Names::last(string $locale = 'en', ?Gender $gender = null)` | `ArbitraryInterface<non-empty-string>` | Склоняется по роду там, где этого требует локаль |
| `Names::middle(string $locale, ?Gender $gender = null)` | `ArbitraryInterface<non-empty-string>` | Отчества; локаль **обязательна** — набор не универсален |
| `Names::full(string $locale = 'en', ?Gender $gender = null, bool $middle = false)` | `ArbitraryInterface<non-empty-string>` | `Имя [Отчество] Фамилия`, рендер из `person()` |
| `Names::person(string $locale = 'en', ?Gender $gender = null, bool $middle = false)` | `ArbitraryInterface<PersonName>` | Части, собранные вместе |

`PersonName` — `final readonly class` с полями `$first`, `$middle` (nullable),
`$last`, `$gender` и тремя формами отображения:

| Метод | `en` | `ru` |
|---|---|---|
| `full()` | `John Smith` | `Иван Иванович Иванов` |
| `initialLast()` | `J. Smith` | `И. Иванов` |
| `lastInitials()` | `Smith J.` | `Иванов И. И.` |

Любая другая форма — один `Gen::map()`:

```php
Gen::map(Names::person(), static fn (PersonName $p): string => $p->last . ', ' . $p->first);
```

### Локали

| Локаль | Имена | Фамилии | Отчества |
|---|---|---|---|
| `en` | 50 мужских + 50 женских | 100, общие для обоих родов | — |
| `ru` | 50 мужских + 50 женских | 50 + 50, попарно выровнены по индексу | 40 + 40, одни и те же основы в обоих списках |

Без указания рода выборка идёт из объединения двух списков, и объединение
выбрасывает то, что у них общее: `Names::last('en')` тянет из 100 фамилий, а не
из тех же 100, перечисленных дважды. Выбор равномерен по индексу, поэтому
дубль — это удвоенная вероятность записи.

Незарегистрированная локаль даёт `InvalidArgumentException` в момент
**построения** arbitrary, а не при первой генерации значения; то же самое —
при запросе отчеств у `en`. Теги локалей сравниваются буквально: `'EN'`,
`'en-US'` и `'en '` — неизвестны.

### Согласованность рода

`Names::first()` и `Names::last()` — независимые выборки, и склейка их руками
может дать `Мария Иванов`, чего русский язык не порождает. Когда части обязаны
согласовываться, тянуть их нужно вместе:

```php
$person = Names::person('ru', middle: true);   // Мария Ивановна Иванова
```

У `Gender` два случая, `Male` и `Female`, объявленные именно в этом порядке:
шринкинг идёт к первому случаю.

## Безопасность

Списки — синтетические тестовые данные: это не реестр реальных людей и не
претензия на культурную полноту. Значения — печатный UTF-8 без управляющих
символов, поэтому их безопасно вставлять в отчёты и сообщения о падениях; но
это всё ещё сгенерированный вход, и тестируемый код обязан валидировать его как
любые пользовательские данные.

Изменение наборов меняет значения, которые даёт конкретный seed, поэтому такие
правки выходят **минорными** релизами и перечисляются в
[CHANGELOG.md](CHANGELOG.md).

## Примеры

Исполняемые скрипты — в [`examples/`](examples/README.md).

## Разработка

```bash
make build          # validate + normalize + require-checker + cs + psalm + test
make cs-fix
make psalm
make test
make test-coverage
make mutation
make release-check
```

PHP на хосте не нужен — все цели выполняются в Docker-образе `composer:2`.

## Лицензия

BSD-3-Clause. См. [LICENSE.md](LICENSE.md).
