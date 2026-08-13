<?php

declare(strict_types=1);

use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Names\Gender;
use Rasuvaeff\PropertyTesting\Names\Names;
use Rasuvaeff\PropertyTesting\Names\PersonName;

require dirname(__DIR__) . '/vendor/autoload.php';

// person() keeps the parts, so every display form is derived from one
// grammatically consistent draw instead of three independent ones.
foreach (Gen::sample(Names::person('ru', middle: true), 4, 11) as $person) {
    \assert($person instanceof PersonName);

    // printf padding counts bytes, not characters, so Cyrillic columns are
    // joined explicitly instead of padded.
    printf(
        "%s: %s | %s | %s\n",
        $person->gender->name,
        $person->full(),
        $person->initialLast(),
        $person->lastInitials(),
    );
}

// Surnames and patronymics are inflected: a female draw never yields "Иванов".
$female = Gen::sample(Names::person('ru', Gender::Female, middle: true), 3, 12);
$male = Gen::sample(Names::person('ru', Gender::Male, middle: true), 3, 12);

printf("\nFemale: %s\n", implode(', ', array_map(static fn(PersonName $p): string => $p->full(), $female)));
printf("Male:   %s\n", implode(', ', array_map(static fn(PersonName $p): string => $p->full(), $male)));

// Any display form the value object does not provide is one Gen::map away.
$sortable = Gen::map(
    Names::person('en'),
    static fn(PersonName $person): string => $person->last . ', ' . $person->first,
);

printf("\nSortable: %s\n", implode(' | ', Gen::sample($sortable, 3, 13)));

// English carries no patronymic dataset, and says so instead of inventing one.
try {
    Names::person(middle: true);
} catch (InvalidArgumentException $exception) {
    printf("\nMiddle names in en: %s\n", $exception->getMessage());
}
