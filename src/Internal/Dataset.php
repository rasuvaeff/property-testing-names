<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Names\Internal;

use Rasuvaeff\PropertyTesting\Names\Gender;

/**
 * One locale's name lists.
 *
 * Every list is ordered from the shortest, most common entry to the rarest:
 * generators shrink toward earlier entries, so a counterexample minimises into
 * a plain name instead of a long exotic one. Male and female lists of the same
 * kind are index-aligned where a locale inflects them (`ru`), which is what
 * keeps `Иван Иванович Иванов` and `Мария Ивановна Иванова` both spellable.
 *
 * @internal
 */
final readonly class Dataset
{
    /**
     * @param non-empty-string $locale
     * @param non-empty-list<non-empty-string> $maleFirstNames
     * @param non-empty-list<non-empty-string> $femaleFirstNames
     * @param non-empty-list<non-empty-string> $maleLastNames
     * @param non-empty-list<non-empty-string> $femaleLastNames
     * @param list<non-empty-string> $maleMiddleNames Empty when the locale has no dataset for them
     * @param list<non-empty-string> $femaleMiddleNames Empty when the locale has no dataset for them
     */
    public function __construct(
        public string $locale,
        public array $maleFirstNames,
        public array $femaleFirstNames,
        public array $maleLastNames,
        public array $femaleLastNames,
        public array $maleMiddleNames = [],
        public array $femaleMiddleNames = [],
    ) {}

    /**
     * @return non-empty-list<non-empty-string>
     */
    public function firstNames(?Gender $gender): array
    {
        return match ($gender) {
            Gender::Male => $this->maleFirstNames,
            Gender::Female => $this->femaleFirstNames,
            null => self::pool([...$this->maleFirstNames, ...$this->femaleFirstNames]),
        };
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    public function lastNames(?Gender $gender): array
    {
        return match ($gender) {
            Gender::Male => $this->maleLastNames,
            Gender::Female => $this->femaleLastNames,
            null => self::pool([...$this->maleLastNames, ...$this->femaleLastNames]),
        };
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    public function middleNames(?Gender $gender): array
    {
        $names = match ($gender) {
            Gender::Male => $this->maleMiddleNames,
            Gender::Female => $this->femaleMiddleNames,
            null => [...$this->maleMiddleNames, ...$this->femaleMiddleNames],
        };

        if ($names === []) {
            throw new \InvalidArgumentException(
                sprintf('Locale "%s" has no middle-name (patronymic) dataset', $this->locale),
            );
        }

        return self::pool($names);
    }

    public function hasMiddleNames(): bool
    {
        return $this->maleMiddleNames !== [] && $this->femaleMiddleNames !== [];
    }

    /**
     * The pool a generator picks from, with duplicates removed.
     *
     * Picking is uniform over the index, so an entry present twice is an entry
     * with twice the probability — the defect every single list is checked
     * against. Merging two lists reintroduces it: `en` hands the same surname
     * list to both genders, so the unfiltered pool held all 100 surnames
     * twice. First occurrence wins, which keeps the shortest-first order the
     * shrinker descends through.
     *
     * @param non-empty-list<non-empty-string> $names
     *
     * @return non-empty-list<non-empty-string>
     */
    private static function pool(array $names): array
    {
        return array_values(array_unique($names));
    }
}
