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
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(PersonName::class)]
// Gender has no behaviour of its own; its contract is the declaration order
// (shrinking runs toward the first case) asserted below, plus the gendered
// lookups exercised through every PersonName built here.
#[Covers(Gender::class)]
final class PersonNameTest
{
    #[DataProvider('renderingProvider')]
    public function rendersEveryDisplayForm(
        PersonName $name,
        string $full,
        string $initialLast,
        string $lastInitials,
    ): void {
        Assert::same($name->full(), $full);
        Assert::same((string) $name, $full);
        Assert::same($name->initialLast(), $initialLast);
        Assert::same($name->lastInitials(), $lastInitials);
    }

    public static function renderingProvider(): iterable
    {
        yield 'english, no middle name' => [
            new PersonName('John', null, 'Smith', Gender::Male),
            'John Smith',
            'J. Smith',
            'Smith J.',
        ];

        yield 'english with a middle name' => [
            new PersonName('John', 'Adam', 'Smith', Gender::Male),
            'John Adam Smith',
            'J. Smith',
            'Smith J. A.',
        ];

        yield 'russian male with a patronymic' => [
            new PersonName('Иван', 'Иванович', 'Иванов', Gender::Male),
            'Иван Иванович Иванов',
            'И. Иванов',
            'Иванов И. И.',
        ];

        yield 'russian female with a patronymic' => [
            new PersonName('Мария', 'Ивановна', 'Иванова', Gender::Female),
            'Мария Ивановна Иванова',
            'М. Иванова',
            'Иванова М. И.',
        ];
    }

    public function isBuildableFromItsSignatureWhenRejectionsAreSkipped(): void
    {
        // The psalm types promise non-empty parts, so forClass draws from
        // non-empty generators; a whitespace-only string is one of those, and
        // the constructor refuses it. skipInvalid redraws a refused argument
        // set and prunes refused shrink candidates instead of failing the run.
        $random = new Random(11);
        $arbitrary = Gen::forClass(PersonName::class, skipInvalid: true);

        for ($i = 0; $i < 200; ++$i) {
            $node = $arbitrary->generate($random);

            Assert::instanceOf($node->value, PersonName::class);
            Assert::true(trim($node->value->first) !== '' && trim($node->value->last) !== '');
            Assert::true($node->value->middle === null || trim($node->value->middle) !== '');

            foreach ($node->shrinks() as $candidate) {
                Assert::instanceOf($candidate->value, PersonName::class);
            }
        }
    }

    public function keepsPartsAndGenderAsGiven(): void
    {
        $name = new PersonName('Мария', 'Ивановна', 'Иванова', Gender::Female);

        Assert::same($name->first, 'Мария');
        Assert::same($name->middle, 'Ивановна');
        Assert::same($name->last, 'Иванова');
        Assert::same($name->gender, Gender::Female);
    }

    #[DataProvider('blankPartProvider')]
    public function rejectsBlankParts(string $first, ?string $middle, string $last, string $message): void
    {
        try {
            new PersonName($first, $middle, $last, Gender::Male);
            Assert::fail('A blank part was accepted');
        } catch (\InvalidArgumentException $exception) {
            Assert::same($exception->getMessage(), $message);
        }
    }

    public static function blankPartProvider(): iterable
    {
        yield 'empty first name' => ['', null, 'Smith', 'First name must not be empty'];
        yield 'whitespace first name' => [' ', null, 'Smith', 'First name must not be empty'];
        yield 'tab first name' => ["\t", null, 'Smith', 'First name must not be empty'];
        yield 'empty middle name' => ['John', '', 'Smith', 'Middle name must not be empty'];
        yield 'whitespace middle name' => ['John', '  ', 'Smith', 'Middle name must not be empty'];
        yield 'empty last name' => ['John', null, '', 'Last name must not be empty'];
        yield 'newline last name' => ['John', null, "\n ", 'Last name must not be empty'];
        yield 'first name reported before the others' => [' ', ' ', ' ', 'First name must not be empty'];
        yield 'middle name reported before the last' => ['John', ' ', ' ', 'Middle name must not be empty'];
    }

    public function acceptsPartsPaddedWithWhitespaceAsGiven(): void
    {
        // Only blank parts are rejected; padding is the caller's data and is
        // neither trimmed nor refused.
        $name = new PersonName(' John', null, 'Smith ', Gender::Male);

        Assert::same($name->first, ' John');
        Assert::same($name->last, 'Smith ');
    }

    public function isStringableAsItsFullForm(): void
    {
        $name = new PersonName('Иван', 'Иванович', 'Иванов', Gender::Male);

        Assert::instanceOf($name, \Stringable::class);
        Assert::same((string) $name, 'Иван Иванович Иванов');
        Assert::same(sprintf('%s', $name), 'Иван Иванович Иванов');
    }

    public function serialisesToJsonAsPlainData(): void
    {
        $name = new PersonName('Иван', 'Иванович', 'Иванов', Gender::Male);

        Assert::same(
            json_encode($name, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            '{"first":"Иван","middle":"Иванович","last":"Иванов","gender":"male"}',
        );
        Assert::same(
            json_encode(new PersonName('Emma', null, 'Smith', Gender::Female), JSON_THROW_ON_ERROR),
            '{"first":"Emma","middle":null,"last":"Smith","gender":"female"}',
        );
    }

    public function genderIsStringBacked(): void
    {
        Assert::same(Gender::Male->value, 'male');
        Assert::same(Gender::Female->value, 'female');
        Assert::same(Gender::from('female'), Gender::Female);
    }

    /**
     * Initials must not follow `mb_internal_encoding()`: a host application is
     * free to set it to a single-byte encoding, and a Cyrillic initial would
     * then be cut mid-character into invalid UTF-8.
     */
    public function initialsIgnoreTheAmbientInternalEncoding(): void
    {
        $previous = mb_internal_encoding();

        try {
            mb_internal_encoding('ISO-8859-1');
            $name = new PersonName('Иван', 'Иванович', 'Иванов', Gender::Male);

            Assert::same($name->initialLast(), 'И. Иванов');
            Assert::same($name->lastInitials(), 'Иванов И. И.');
        } finally {
            mb_internal_encoding($previous === false ? 'UTF-8' : $previous);
        }
    }

    public function genderCasesAreDeclaredInShrinkingOrder(): void
    {
        Assert::same(Gender::cases(), [Gender::Male, Gender::Female]);
    }

    /**
     * Initials are taken with `mb_substr`, so a Cyrillic first letter must
     * survive as one character rather than a broken leading byte.
     */
    #[Property(runs: 300, timeoutMs: 2_000)]
    public function initialsAreSingleCharactersOfTheirParts(string $locale): void
    {
        $middle = $locale === 'ru' && Gen::draw(Gen::bool());
        $person = Gen::draw(Names::person($locale, middle: $middle));

        Classify::label('locale: ' . $locale);
        Classify::cover($middle, 'middle: present', 10.0);
        Classify::cover(!$middle, 'middle: absent', 10.0);

        $initial = mb_substr($person->first, 0, 1);
        Assert::same($person->initialLast(), $initial . '. ' . $person->last);
        Assert::same(mb_strlen($initial), 1);

        $expected = $person->last . ' ' . $initial . '.';

        if ($person->middle !== null) {
            $expected .= ' ' . mb_substr($person->middle, 0, 1) . '.';
        }

        Assert::same($person->lastInitials(), $expected);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function initialsAreSingleCharactersOfTheirPartsGenerators(): array
    {
        return ['locale' => Gen::elements(Locales::registered())];
    }

    /** @return iterable<string, array{string}> */
    public static function initialsAreSingleCharactersOfTheirPartsExamples(): iterable
    {
        yield 'cyrillic initials' => ['ru'];
        yield 'latin initials' => ['en'];
    }

    /**
     * `full()` joins exactly the parts that are present, separated by single
     * spaces — the display form other packages compare against.
     */
    #[Property(runs: 200, timeoutMs: 2_000)]
    public function fullJoinsThePresentPartsWithSingleSpaces(string $locale): void
    {
        $middle = $locale === 'ru' && Gen::draw(Gen::bool());
        $person = Gen::draw(Names::person($locale, middle: $middle));
        $parts = explode(' ', $person->full());

        Classify::cover($middle, 'middle: present', 10.0);
        Classify::cover(!$middle, 'middle: absent', 10.0);

        Assert::same($parts, $person->middle === null
            ? [$person->first, $person->last]
            : [$person->first, $person->middle, $person->last]);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function fullJoinsThePresentPartsWithSingleSpacesGenerators(): array
    {
        return ['locale' => Gen::elements(Locales::registered())];
    }
}
