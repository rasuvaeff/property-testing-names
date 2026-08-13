<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Names\Internal;

use Rasuvaeff\PropertyTesting\Names\Internal\Data\EnglishDataset;
use Rasuvaeff\PropertyTesting\Names\Internal\Data\RussianDataset;

/**
 * Registry of the bundled locale datasets.
 *
 * There is no runtime registration API on purpose: a global mutable registry
 * makes generated data depend on test execution order.
 *
 * @internal
 */
final class Locales
{
    /** @var non-empty-list<non-empty-string> */
    private const array REGISTERED = ['en', 'ru'];

    private function __construct()
    {
        // Static registry; not instantiable.
    }

    public static function get(string $locale): Dataset
    {
        return match ($locale) {
            'en' => EnglishDataset::create(),
            'ru' => RussianDataset::create(),
            default => throw new \InvalidArgumentException(
                sprintf(
                    'Unknown locale "%s"; registered locales are %s',
                    $locale,
                    implode(', ', self::registered()),
                ),
            ),
        };
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    public static function registered(): array
    {
        return self::REGISTERED;
    }
}
