<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Names\Benchmarks;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Names\Gender;
use Rasuvaeff\PropertyTesting\Names\Names;
use Rasuvaeff\PropertyTesting\Names\PersonName;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert\ExpectNoAssertions;
use Testo\Bench;

final class NamesBench
{
    /** @var ArbitraryInterface<non-empty-string>|null */
    private static ?ArbitraryInterface $prebuiltFirstNames = null;

    /**
     * Every factory call rebuilds the locale dataset. A generators method
     * builds its arbitraries once per property, so the gap against the
     * prebuilt variant is the price of building inside a hot loop.
     */
    #[ExpectNoAssertions]
    #[Bench(['prebuilt arbitrary' => [self::class, 'generateFromPrebuiltArbitrary']], calls: 1_000, iterations: 5)]
    public static function generateFromFreshArbitrary(): string
    {
        $value = Names::first('ru')->generate(new Random(123))->value;
        \assert(\is_string($value));

        return $value;
    }

    public static function generateFromPrebuiltArbitrary(): string
    {
        self::$prebuiltFirstNames ??= Names::first('ru');

        $value = self::$prebuiltFirstNames->generate(new Random(123))->value;
        \assert(\is_string($value));

        return $value;
    }

    /**
     * A patronymic adds a third dataset lookup to the tuple; `full()` adds the
     * rendering on top of the same draw.
     */
    #[ExpectNoAssertions]
    #[Bench(['rendered as a string' => [self::class, 'generateFullString']], calls: 1_000, iterations: 5)]
    public static function generatePersonWithPatronymic(): PersonName
    {
        $value = Names::person('ru', Gender::Female, middle: true)->generate(new Random(123))->value;
        \assert($value instanceof PersonName);

        return $value;
    }

    public static function generateFullString(): string
    {
        $value = Names::full('ru', Gender::Female, middle: true)->generate(new Random(123))->value;
        \assert(\is_string($value));

        return $value;
    }

    /**
     * Display forms are pure string work; `lastInitials()` walks one more part
     * with `mb_substr` when a patronymic is present.
     */
    #[ExpectNoAssertions]
    #[Bench(['lastInitials()' => [self::class, 'renderLastInitials']], calls: 10_000, iterations: 5)]
    public static function renderFull(): string
    {
        return self::sample()->full();
    }

    public static function renderLastInitials(): string
    {
        return self::sample()->lastInitials();
    }

    private static function sample(): PersonName
    {
        return new PersonName('Мария', 'Ивановна', 'Иванова', Gender::Female);
    }
}
