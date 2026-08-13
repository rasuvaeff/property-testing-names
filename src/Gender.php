<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Names;

/**
 * Grammatical gender of a generated name.
 *
 * Cases are declared in shrinking order: a failing counterexample shrinks
 * toward {@see Gender::Male}, the form most locales spell without a suffix.
 *
 * @api
 */
enum Gender
{
    case Male;
    case Female;
}
