<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Names;

/**
 * One generated person name, kept as parts so a test can assert on any of
 * them and render whichever display form it needs.
 *
 * All parts come from the same gendered subset of a locale dataset, so
 * `$first`, `$middle` and `$last` always agree grammatically. `$middle` is
 * `null` unless the generator was asked for it; for `ru` it holds a
 * patronymic.
 *
 * @api
 */
final readonly class PersonName
{
    /**
     * @param non-empty-string $first
     * @param ?non-empty-string $middle
     * @param non-empty-string $last
     */
    public function __construct(
        public string $first,
        public ?string $middle,
        public string $last,
        public Gender $gender,
    ) {
        // The psalm types promise non-empty parts; the runtime guard is for
        // callers static analysis does not see, and lives behind a plain
        // string parameter so the promise and the check do not contradict.
        $this->guardNotEmpty($first, 'Person name parts must not be empty');
        $this->guardNotEmpty($last, 'Person name parts must not be empty');

        if ($middle !== null) {
            $this->guardNotEmpty($middle, 'Middle name must be null or a non-empty string');
        }
    }

    private function guardNotEmpty(string $part, string $message): void
    {
        if ($part === '') {
            throw new \InvalidArgumentException($message);
        }
    }

    /**
     * `First [Middle] Last`, e.g. `John Smith` or `Иван Иванович Иванов`.
     *
     * @return non-empty-string
     */
    public function full(): string
    {
        if ($this->middle === null) {
            return $this->first . ' ' . $this->last;
        }

        return $this->first . ' ' . $this->middle . ' ' . $this->last;
    }

    /**
     * `F. Last`, e.g. `J. Smith` or `И. Иванов`.
     *
     * @return non-empty-string
     */
    public function initialLast(): string
    {
        return $this->initialOf($this->first) . ' ' . $this->last;
    }

    /**
     * `Last F. [M.]`, e.g. `Smith J.` or `Иванов И. И.`.
     *
     * @return non-empty-string
     */
    public function lastInitials(): string
    {
        $initials = $this->initialOf($this->first);

        if ($this->middle !== null) {
            $initials .= ' ' . $this->initialOf($this->middle);
        }

        return $this->last . ' ' . $initials;
    }

    /**
     * The encoding is explicit: without it `mb_substr()` follows
     * `mb_internal_encoding()`, which the host application can change globally —
     * a Cyrillic initial would then be cut mid-character.
     *
     * @return non-empty-string
     */
    private function initialOf(string $part): string
    {
        return mb_substr($part, start: 0, length: 1, encoding: 'UTF-8') . '.';
    }
}
