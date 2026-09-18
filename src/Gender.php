<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Names;

/**
 * Grammatical gender of a generated name.
 *
 * Cases are declared in shrinking order: a failing counterexample shrinks
 * toward {@see Gender::Male}, the form most locales spell without a suffix.
 *
 * String-backed so a {@see PersonName} survives `json_encode()` and the case
 * can be stored or compared as plain data; the property regression corpus
 * keys enum cases by name, so the backing values do not change what it
 * replays.
 *
 * @api
 */
enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
}
