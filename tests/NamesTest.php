<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Names\Tests;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Classify;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Names\Gender;
use Rasuvaeff\PropertyTesting\Names\Internal\Locales;
use Rasuvaeff\PropertyTesting\Names\Names;
use Rasuvaeff\PropertyTesting\Names\PersonName;
use Rasuvaeff\PropertyTesting\Property;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(Names::class)]
final class NamesTest
{
    public function defaultLocaleIsEnglish(): void
    {
        Assert::same(Names::DEFAULT_LOCALE, 'en');
        Assert::same(Gen::sample(Names::first(), 5, 7), Gen::sample(Names::first('en'), 5, 7));
    }

    #[DataProvider('unknownLocaleProvider')]
    public function rejectsUnknownLocale(string $locale): void
    {
        try {
            Names::first($locale);
            Assert::fail(sprintf('Locale "%s" was accepted', $locale));
        } catch (\InvalidArgumentException $exception) {
            Assert::string($exception->getMessage())->contains('Unknown locale');
        }
    }

    public static function unknownLocaleProvider(): iterable
    {
        yield 'unregistered' => ['xx'];
        yield 'empty' => [''];
        yield 'case sensitive' => ['EN'];
        yield 'region tag' => ['en-US'];
        yield 'whitespace' => ['en '];
    }

    public function middleRejectsLocaleWithoutDataset(): void
    {
        try {
            Names::middle('en');
            Assert::fail('English was accepted for middle names');
        } catch (\InvalidArgumentException $exception) {
            Assert::same(
                $exception->getMessage(),
                'Locale "en" has no middle-name (patronymic) dataset',
            );
        }
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function personRejectsMiddleNamesForEnglish(): void
    {
        Names::person(middle: true);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function fullRejectsMiddleNamesForEnglish(): void
    {
        Names::full(middle: true);
    }

    /**
     * Deterministic counterpart of the middle-name property: the eager lookup
     * must cover every gender the arbitrary can draw, whether the caller fixed
     * one or left it to the generator. A property alone leaves this to chance —
     * it draws the "middle name requested" branch only in some runs.
     */
    public function personAlwaysCarriesAMiddleNameWhenAsked(): void
    {
        $dataset = Locales::get('ru');

        foreach ([null, Gender::Male, Gender::Female] as $gender) {
            foreach (Gen::sample(Names::person('ru', $gender, middle: true), 10, 5) as $person) {
                Assert::instanceOf($person, PersonName::class);
                Assert::notNull($person->middle);
                Assert::contains($dataset->middleNames($person->gender), $person->middle);
            }
        }
    }

    public function personShrinksTowardTheFirstEntries(): void
    {
        $shrunk = Gen::sampleShrinks(Names::person('ru', Gender::Female), 11);
        $value = $shrunk['value'];
        Assert::instanceOf($value, PersonName::class);

        $firstNames = Locales::get('ru')->firstNames(Gender::Female);
        $lastNames = Locales::get('ru')->lastNames(Gender::Female);

        // Without this the loop below passes vacuously on an empty candidate
        // list — which is exactly what a broken shrink tree would produce.
        Assert::true($shrunk['shrinks'] !== []);
        $reduced = 0;

        foreach ($shrunk['shrinks'] as $candidate) {
            Assert::instanceOf($candidate, PersonName::class);
            Assert::true(
                array_search($candidate->first, $firstNames, strict: true)
                <= array_search($value->first, $firstNames, strict: true),
            );
            Assert::true(
                array_search($candidate->last, $lastNames, strict: true)
                <= array_search($value->last, $lastNames, strict: true),
            );

            if ($candidate->first !== $value->first || $candidate->last !== $value->last) {
                ++$reduced;
            }
        }

        Assert::true($reduced > 0);
    }

    #[Property(runs: 200, timeoutMs: 2_000)]
    public function firstAndLastNamesComeFromTheDataset(string $locale, ?Gender $gender): void
    {
        $dataset = Locales::get($locale);

        Classify::label('locale: ' . $locale);
        Classify::when($gender === null, 'gender: unspecified');
        Classify::cover($gender === Gender::Male, 'gender: male', 10.0);
        Classify::cover($gender === Gender::Female, 'gender: female', 10.0);

        // Expected pools are assembled from the raw lists rather than from
        // Dataset::firstNames()/lastNames(), so the assertion does not restate
        // the very call the factory makes.
        $expectedFirst = match ($gender) {
            Gender::Male => $dataset->maleFirstNames,
            Gender::Female => $dataset->femaleFirstNames,
            null => array_merge($dataset->maleFirstNames, $dataset->femaleFirstNames),
        };
        $expectedLast = match ($gender) {
            Gender::Male => $dataset->maleLastNames,
            Gender::Female => $dataset->femaleLastNames,
            null => array_merge($dataset->maleLastNames, $dataset->femaleLastNames),
        };

        Assert::contains($expectedFirst, Gen::draw(Names::first($locale, $gender)));
        Assert::contains($expectedLast, Gen::draw(Names::last($locale, $gender)));
    }

    /** @return array<string, ArbitraryInterface> */
    public static function firstAndLastNamesComeFromTheDatasetGenerators(): array
    {
        return [
            'locale' => Gen::elements(Locales::registered()),
            'gender' => Gen::nullable(Gen::enum(Gender::class)),
        ];
    }

    /**
     * Parts of one generated person always belong to the same gendered subset:
     * `ru` inflects surnames and patronymics, so a mismatch would produce
     * "Мария Иванов" — a string no Russian form ever renders.
     */
    #[Property(runs: 300, timeoutMs: 2_000)]
    public function personPartsAgreeWithTheDrawnGender(string $locale, ?Gender $gender): void
    {
        $middle = $locale === 'ru' && Gen::draw(Gen::bool());
        $person = Gen::draw(Names::person($locale, $gender, $middle));
        $dataset = Locales::get($locale);

        Classify::label('locale: ' . $locale);
        Classify::cover($middle, 'middle: present', 10.0);
        Classify::cover(!$middle, 'middle: absent', 10.0);
        Classify::cover($person->gender === Gender::Female, 'drawn: female', 20.0);
        Classify::cover($person->gender === Gender::Male, 'drawn: male', 20.0);

        if ($gender !== null) {
            Assert::same($person->gender, $gender);
        }

        Assert::contains($dataset->firstNames($person->gender), $person->first);
        Assert::contains($dataset->lastNames($person->gender), $person->last);
        Assert::same($person->middle !== null, $middle);

        if ($person->middle !== null) {
            Assert::contains($dataset->middleNames($person->gender), $person->middle);
        }

        if ($locale === 'ru') {
            Assert::same(str_ends_with($person->last, 'а'), $person->gender === Gender::Female);
        }
    }

    /** @return array<string, ArbitraryInterface> */
    public static function personPartsAgreeWithTheDrawnGenderGenerators(): array
    {
        return [
            'locale' => Gen::elements(Locales::registered()),
            'gender' => Gen::nullable(Gen::enum(Gender::class)),
        ];
    }

    /** @return iterable<string, array{string, ?Gender}> */
    public static function personPartsAgreeWithTheDrawnGenderExamples(): iterable
    {
        yield 'russian female (inflected surname)' => ['ru', Gender::Female];
        yield 'russian male' => ['ru', Gender::Male];
        yield 'russian, gender drawn by the generator' => ['ru', null];
        yield 'english, no inflection' => ['en', null];
    }

    /**
     * Every generated name is a plain, printable UTF-8 string: no control
     * characters, no edge whitespace, no empty parts. Rendering a
     * counterexample must never be the thing that breaks the report.
     */
    #[Property(runs: 200, timeoutMs: 2_000)]
    public function generatedNamesArePrintableUtf8(string $locale): void
    {
        $middle = $locale === 'ru';
        $person = Gen::draw(Names::person($locale, middle: $middle));

        foreach ([$person->first, $person->last, $person->full(), $person->lastInitials()] as $value) {
            Assert::true(mb_check_encoding($value, 'UTF-8'));
            Assert::same(preg_match('/\p{C}/u', $value), 0);
            Assert::same(trim($value), $value);
            Assert::notSame($value, '');
        }
    }

    /** @return array<string, ArbitraryInterface> */
    public static function generatedNamesArePrintableUtf8Generators(): array
    {
        return ['locale' => Gen::elements(Locales::registered())];
    }

    /**
     * `full()` is exactly `person()` rendered, not a second implementation:
     * the same seed must produce the same strings through both entry points.
     */
    #[Property(runs: 50, timeoutMs: 2_000)]
    public function fullRendersTheSamePersonForTheSameSeed(string $locale, int $seed): void
    {
        Classify::label('locale: ' . $locale);

        $people = Gen::sample(Names::person($locale), 10, $seed);
        $rendered = Gen::sample(Names::full($locale), 10, $seed);

        Assert::same($rendered, array_map(static fn(PersonName $p): string => $p->full(), $people));
    }

    /** @return array<string, ArbitraryInterface> */
    public static function fullRendersTheSamePersonForTheSameSeedGenerators(): array
    {
        return [
            'locale' => Gen::elements(Locales::registered()),
            'seed' => Gen::intBetween(0, 100_000),
        ];
    }

    /**
     * Locale tags are matched literally, so anything shaped like a BCP 47 tag
     * is rejected rather than silently falling back to the default dataset.
     */
    #[Property(runs: 100, timeoutMs: 2_000)]
    public function bcp47StyleTagsAreRejected(string $locale): void
    {
        try {
            Names::first($locale);
            Assert::fail(sprintf('Locale "%s" was accepted', $locale));
        } catch (\InvalidArgumentException $exception) {
            Assert::string($exception->getMessage())->contains($locale);
        }
    }

    /** @return array<string, ArbitraryInterface> */
    public static function bcp47StyleTagsAreRejectedGenerators(): array
    {
        return ['locale' => Gen::stringMatching('[a-z]{2}-[A-Z]{2}')];
    }

    /** @return iterable<string, array{string}> */
    public static function bcp47StyleTagsAreRejectedExamples(): iterable
    {
        yield 'registered language, unregistered tag' => ['en-US'];
        yield 'russian region tag' => ['ru-RU'];
    }
}
