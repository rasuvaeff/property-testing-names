<?php

declare(strict_types=1);

use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Names\Gender;
use Rasuvaeff\PropertyTesting\Names\Names;

require dirname(__DIR__) . '/vendor/autoload.php';

// Every factory returns a plain arbitrary; Gen::sample() draws from it eagerly
// for a fixed seed, which is what makes this script reproducible.
printf("English first names: %s\n", implode(', ', Gen::sample(Names::first(), 5, 1)));
printf("English surnames:    %s\n", implode(', ', Gen::sample(Names::last(), 5, 2)));
printf("English full names:  %s\n", implode(', ', Gen::sample(Names::full(), 3, 3)));

printf("\nRussian female names: %s\n", implode(', ', Gen::sample(Names::first('ru', Gender::Female), 5, 4)));
printf("Russian patronymics:  %s\n", implode(', ', Gen::sample(Names::middle('ru', Gender::Male), 5, 5)));
printf("Russian full names:   %s\n", implode(' | ', Gen::sample(Names::full('ru', middle: true), 3, 6)));

// Names compose with the core generators like any other arbitrary.
$emails = Gen::map(
    Gen::tuple(Names::first('en', Gender::Female), Gen::elements(['@example.com', '@example.org'])),
    static fn(array $parts): string => mb_strtolower((string) $parts[0]) . (string) $parts[1],
);

printf("\nDerived addresses: %s\n", implode(', ', Gen::sample($emails, 3, 7)));

// An unknown locale fails when the arbitrary is built, not mid-run.
try {
    Names::first('de');
} catch (InvalidArgumentException $exception) {
    printf("\nUnknown locale: %s\n", $exception->getMessage());
}
