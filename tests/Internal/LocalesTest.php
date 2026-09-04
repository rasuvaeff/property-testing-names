<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Names\Tests\Internal;

use Rasuvaeff\PropertyTesting\Names\Gender;
use Rasuvaeff\PropertyTesting\Names\Internal\Data\EnglishDataset;
use Rasuvaeff\PropertyTesting\Names\Internal\Data\RussianDataset;
use Rasuvaeff\PropertyTesting\Names\Internal\Dataset;
use Rasuvaeff\PropertyTesting\Names\Internal\Locales;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(Locales::class)]
// The bundled data classes are pure factories for Dataset: Locales::get() is
// the only caller, so their lists are audited here rather than in a test class
// that would repeat the same assertions per locale.
#[Covers(EnglishDataset::class)]
#[Covers(RussianDataset::class)]
final class LocalesTest
{
    public function registersEnglishAndRussian(): void
    {
        Assert::same(Locales::registered(), ['en', 'ru']);
    }

    public function buildsTheRequestedDataset(): void
    {
        Assert::same(Locales::get('en')->locale, 'en');
        Assert::same(Locales::get('ru')->locale, 'ru');
        Assert::equals(Locales::get('en'), EnglishDataset::create());
        Assert::equals(Locales::get('ru'), RussianDataset::create());
    }

    public function unknownLocaleListsTheRegisteredOnes(): void
    {
        try {
            Locales::get('de');
            Assert::fail('An unregistered locale was accepted');
        } catch (\InvalidArgumentException $exception) {
            Assert::same(
                $exception->getMessage(),
                'Unknown locale "de"; registered locales are en, ru',
            );
        }
    }

    public function englishHasNoMiddleNamesAndShareSurnames(): void
    {
        $dataset = Locales::get('en');

        Assert::false($dataset->hasMiddleNames());
        Assert::same($dataset->maleLastNames, $dataset->femaleLastNames);
    }

    public function russianInflectsSurnamesAndPatronymics(): void
    {
        $dataset = Locales::get('ru');

        Assert::true($dataset->hasMiddleNames());
        Assert::same(count($dataset->maleLastNames), count($dataset->femaleLastNames));
        Assert::same(count($dataset->maleMiddleNames), count($dataset->femaleMiddleNames));

        foreach ($dataset->femaleLastNames as $index => $surname) {
            // The class documents the two lists as index-aligned pairs of one
            // stem, and `person()` spells a family from that pairing. Checking
            // only the ending and the difference would pass on a permutation of
            // two female entries, which would marry the wrong pair.
            Assert::same($surname, $dataset->maleLastNames[$index] . 'а');
        }

        foreach ($dataset->maleLastNames as $surname) {
            Assert::false(str_ends_with($surname, 'а'));
        }

        // Patronymics carry the same stems in both genders, but the female form
        // does not grow from the male one by a constant suffix, so the lists are
        // not index-aligned: each is ordered by its own length.
        foreach ($dataset->femaleMiddleNames as $patronymic) {
            Assert::true(str_ends_with($patronymic, 'на'));
        }

        foreach ($dataset->maleMiddleNames as $patronymic) {
            Assert::true(str_ends_with($patronymic, 'ич'));
        }
    }

    #[DataProvider('ungenderedPoolProvider')]
    public function anUngenderedPoolHasNoDuplicates(string $locale, string $kind, array $names, int $expectedCount): void
    {
        // The gendered lists are checked one by one below; this is the pool a
        // caller who passes no gender actually draws from. `en` hands the same
        // surname list to both genders, so an unfiltered merge held all 100
        // surnames twice and doubled every surname's weight.
        Assert::same(count(array_unique($names)), count($names), sprintf('%s/%s has duplicates', $locale, $kind));
        Assert::same(count($names), $expectedCount, sprintf('%s/%s changed size', $locale, $kind));
    }

    /**
     * @return iterable<string, array{string, string, list<string>, int}>
     */
    public static function ungenderedPoolProvider(): iterable
    {
        foreach (['en' => 100, 'ru' => 100] as $locale => $surnames) {
            $dataset = Locales::get($locale);

            yield $locale . ' first names' => [$locale, 'first names', $dataset->firstNames(null), 100];
            yield $locale . ' last names' => [$locale, 'last names', $dataset->lastNames(null), $surnames];
        }

        yield 'ru patronymics' => ['ru', 'patronymics', Locales::get('ru')->middleNames(null), 80];
    }

    /**
     * Every list is the input space of a generator: a duplicate silently
     * doubles an entry's probability, an empty or padded entry travels into
     * assertions as a value no dataset was supposed to contain.
     */
    #[DataProvider('listProvider')]
    public function listsAreCleanAndDiverse(string $locale, string $kind, array $names, int $expectedCount): void
    {
        // Exact counts, not a lower bound: README, README.ru and the plan all
        // publish these numbers, so a data edit that drifts them fails here
        // instead of silently making the documentation wrong.
        Assert::same(count($names), $expectedCount, sprintf('%s/%s changed size', $locale, $kind));
        Assert::same(count(array_unique($names)), count($names), sprintf('%s/%s has duplicates', $locale, $kind));

        foreach ($names as $name) {
            Assert::true(is_string($name));
            Assert::notSame($name, '');
            Assert::true(mb_check_encoding($name, 'UTF-8'), sprintf('%s in %s/%s is not UTF-8', $name, $locale, $kind));
            Assert::same(preg_match('/\p{C}/u', $name), 0, sprintf('%s in %s/%s has control characters', $name, $locale, $kind));
            Assert::same(trim($name), $name, sprintf('%s in %s/%s has edge whitespace', $name, $locale, $kind));
            Assert::same(mb_substr($name, 0, 1), mb_strtoupper(mb_substr($name, 0, 1)), sprintf('%s in %s/%s is not capitalised', $name, $locale, $kind));
        }
    }

    public static function listProvider(): iterable
    {
        foreach (['en' => 100, 'ru' => 50] as $locale => $surnames) {
            $dataset = Locales::get($locale);

            yield $locale . ' male first names' => [$locale, 'male first names', $dataset->maleFirstNames, 50];
            yield $locale . ' female first names' => [$locale, 'female first names', $dataset->femaleFirstNames, 50];
            yield $locale . ' male last names' => [$locale, 'male last names', $dataset->maleLastNames, $surnames];
            yield $locale . ' female last names' => [$locale, 'female last names', $dataset->femaleLastNames, $surnames];
        }

        $russian = Locales::get('ru');

        yield 'ru male patronymics' => ['ru', 'male patronymics', $russian->maleMiddleNames, 40];
        yield 'ru female patronymics' => ['ru', 'female patronymics', $russian->femaleMiddleNames, 40];
    }

    /**
     * Shrinking walks toward the head of each list, so the head must hold the
     * shortest entries — otherwise a minimised counterexample is longer than
     * the value it replaced.
     */
    #[DataProvider('shrinkOrderProvider')]
    public function entriesNeverGetShorterDownTheList(Dataset $dataset, Gender $gender): void
    {
        $lists = [$dataset->firstNames($gender), $dataset->lastNames($gender)];

        // Patronymics included for both genders: the two lists carry the same
        // stems, each in its own length order, because a female form does not
        // grow from its male form by a constant suffix.
        if ($dataset->hasMiddleNames()) {
            $lists[] = $dataset->middleNames($gender);
        }

        foreach ($lists as $names) {
            $lengths = array_map(mb_strlen(...), $names);
            $sorted = $lengths;
            sort($sorted);

            Assert::same($lengths, $sorted);
        }
    }

    public static function shrinkOrderProvider(): iterable
    {
        foreach (['en', 'ru'] as $locale) {
            foreach (Gender::cases() as $gender) {
                yield $locale . ' ' . $gender->name => [Locales::get($locale), $gender];
            }
        }
    }
}
