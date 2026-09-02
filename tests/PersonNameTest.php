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
use Testo\Assert\ExpectException;
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

    public function isBuildableFromItsSignatureByForClass(): void
    {
        // The psalm types promise non-empty parts, so forClass draws from
        // non-empty generators and the constructor accepts every instance —
        // including while shrinking, where a refused candidate would be lost.
        $random = new Random(11);
        $arbitrary = Gen::forClass(PersonName::class);

        for ($i = 0; $i < 200; ++$i) {
            $node = $arbitrary->generate($random);

            Assert::instanceOf($node->value, PersonName::class);
            Assert::true($node->value->first !== '' && $node->value->last !== '' && $node->value->middle !== '');

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

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsEmptyFirstName(): void
    {
        new PersonName('', null, 'Smith', Gender::Male);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsEmptyLastName(): void
    {
        new PersonName('John', null, '', Gender::Male);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsEmptyMiddleName(): void
    {
        new PersonName('John', '', 'Smith', Gender::Male);
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
