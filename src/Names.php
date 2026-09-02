<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Names;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Names\Internal\Locales;

/**
 * Static factories for person-name arbitraries, the domain counterpart of
 * {@see Gen} — kept in its own package because name lists are versioned data
 * with their own update policy, not a primitive of the engine.
 *
 * Every factory returns a plain {@see ArbitraryInterface}, so names compose
 * with the core generators:
 *
 * ```php
 * Gen::tuple(Names::first(), Gen::elements(['@example.com', '@example.org']));
 * Gen::map(Names::person(locale: 'ru'), static fn (PersonName $n) => $n->lastInitials());
 * ```
 *
 * Values shrink toward earlier dataset entries, so a counterexample minimises
 * into a short, ordinary name instead of turning into arbitrary text. An
 * unknown locale is rejected when the arbitrary is built, not when it first
 * generates a value.
 *
 * The data is synthetic test material: it is neither a register of real people
 * nor a claim of cultural completeness.
 *
 * @api
 */
final readonly class Names
{
    public const string DEFAULT_LOCALE = 'en';

    private function __construct()
    {
        // Static facade; not instantiable.
    }

    /**
     * Whole names with grammatically consistent parts: `$first`, `$middle` and
     * `$last` are drawn from the same gendered subset of the locale.
     *
     * @param non-empty-string $locale One of the registered locales (`en`, `ru`)
     * @param bool $middle Include a middle name (a patronymic for `ru`)
     *
     * @throws \InvalidArgumentException When the locale is unknown, or has no middle-name dataset
     *
     * @return ArbitraryInterface<PersonName>
     */
    public static function person(
        string $locale = self::DEFAULT_LOCALE,
        ?Gender $gender = null,
        bool $middle = false,
    ): ArbitraryInterface {
        $dataset = Locales::get($locale);

        /** @var array<string, non-empty-list<non-empty-string>> $middleNames */
        $middleNames = [];

        if ($middle) {
            foreach ($gender === null ? Gender::cases() : [$gender] as $case) {
                $middleNames[$case->name] = $dataset->middleNames($case);
            }
        }

        return Gen::flatMap(
            self::genderArbitrary($gender),
            /** @return ArbitraryInterface<PersonName> */
            static function (Gender $drawn) use ($dataset, $middleNames): ArbitraryInterface {
                // A one-entry pool of null rather than Gen::constant(null):
                // ArbitraryInterface's TValue is invariant, so the union of two
                // concrete arbitraries would need a widening annotation that no
                // analyser accepts. One pool, one type, same generated values.
                /** @var non-empty-list<non-empty-string|null> $middlePool */
                $middlePool = $middleNames[$drawn->name] ?? [null];

                // Nested rather than a Gen::tuple(): a tuple erases its element
                // types to mixed, which would force type assertions back into
                // the mapping closure.
                return Gen::flatMap(
                    Gen::elements($dataset->firstNames($drawn)),
                    /**
                     * @param non-empty-string $first
                     * @return ArbitraryInterface<PersonName>
                     */
                    static fn(string $first): ArbitraryInterface => Gen::flatMap(
                        Gen::elements($middlePool),
                        /**
                         * @param ?non-empty-string $middle
                         * @return ArbitraryInterface<PersonName>
                         */
                        static fn(?string $middle): ArbitraryInterface => Gen::map(
                            Gen::elements($dataset->lastNames($drawn)),
                            /** @param non-empty-string $last */
                            static fn(string $last): PersonName => new PersonName(
                                first: $first,
                                middle: $middle,
                                last: $last,
                                gender: $drawn,
                            ),
                        ),
                    ),
                );
            },
        );
    }

    /**
     * Given names. Without an explicit gender the male and female lists are
     * merged, so a single first name carries no gender promise — use
     * {@see person()} when other parts must agree with it.
     *
     * @param non-empty-string $locale
     *
     * @throws \InvalidArgumentException When the locale is unknown
     *
     * @return ArbitraryInterface<non-empty-string>
     */
    public static function first(string $locale = self::DEFAULT_LOCALE, ?Gender $gender = null): ArbitraryInterface
    {
        return Gen::elements(Locales::get($locale)->firstNames($gender));
    }

    /**
     * Surnames, inflected per gender where the locale requires it.
     *
     * @param non-empty-string $locale
     *
     * @throws \InvalidArgumentException When the locale is unknown
     *
     * @return ArbitraryInterface<non-empty-string>
     */
    public static function last(string $locale = self::DEFAULT_LOCALE, ?Gender $gender = null): ArbitraryInterface
    {
        return Gen::elements(Locales::get($locale)->lastNames($gender));
    }

    /**
     * Middle names — patronymics for `ru`. The locale is required because
     * this dataset is not universal: `en` carries none and is rejected.
     *
     * @param non-empty-string $locale
     *
     * @throws \InvalidArgumentException When the locale is unknown or has no middle-name dataset
     *
     * @return ArbitraryInterface<non-empty-string>
     */
    public static function middle(string $locale, ?Gender $gender = null): ArbitraryInterface
    {
        return Gen::elements(Locales::get($locale)->middleNames($gender));
    }

    /**
     * Display names `First [Middle] Last`, built from {@see person()} and thus
     * grammatically consistent. Other display forms come from the value object:
     * `Gen::map(Names::person(), static fn (PersonName $n) => $n->initialLast())`.
     *
     * @param non-empty-string $locale
     * @param bool $middle Include a middle name (a patronymic for `ru`)
     *
     * @throws \InvalidArgumentException When the locale is unknown, or has no middle-name dataset
     *
     * @return ArbitraryInterface<non-empty-string>
     */
    public static function full(
        string $locale = self::DEFAULT_LOCALE,
        ?Gender $gender = null,
        bool $middle = false,
    ): ArbitraryInterface {
        return Gen::map(
            self::person($locale, $gender, $middle),
            /** @return non-empty-string */
            static fn(PersonName $name): string => $name->full(),
        );
    }

    /**
     * @return ArbitraryInterface<Gender>
     */
    private static function genderArbitrary(?Gender $gender): ArbitraryInterface
    {
        return $gender === null ? Gen::enum(Gender::class) : Gen::constant($gender);
    }
}
