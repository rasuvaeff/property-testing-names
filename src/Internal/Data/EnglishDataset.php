<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Names\Internal\Data;

use Rasuvaeff\PropertyTesting\Names\Internal\Dataset;

/**
 * English (`en`) name lists: synthetic test data, not a register of real
 * people. Surnames are not inflected, so both genders share one list; the
 * locale carries no middle-name dataset.
 *
 * @internal
 */
final class EnglishDataset
{
    /** @var non-empty-list<non-empty-string> */
    private const array MALE_FIRST_NAMES = [
        'Ian', 'Joe', 'Max', 'Sam', 'Tom', 'Ben', 'Dan', 'Jay', 'Leo', 'Ray',
        'Adam', 'Alan', 'Carl', 'Dean', 'Eric', 'Evan', 'Gary', 'Greg', 'Jack', 'John',
        'Luke', 'Mark', 'Neil', 'Noah', 'Owen', 'Paul', 'Ryan', 'Sean', 'Todd', 'Brian',
        'Chris', 'Craig', 'David', 'Frank', 'Harry', 'Henry', 'Jacob', 'James', 'Jason', 'Keith',
        'Kevin', 'Peter', 'Simon', 'Steve', 'Andrew', 'Daniel', 'George', 'Joseph', 'Michael', 'Nicholas',
    ];

    /** @var non-empty-list<non-empty-string> */
    private const array FEMALE_FIRST_NAMES = [
        'Amy', 'Ann', 'Eve', 'Ivy', 'Joy', 'Kim', 'Lea', 'Mia', 'Sue', 'Zoe',
        'Anna', 'Beth', 'Cara', 'Dana', 'Emma', 'Erin', 'Gail', 'Jane', 'Jill', 'Kate',
        'Lisa', 'Lucy', 'Mary', 'Nina', 'Rita', 'Rose', 'Ruth', 'Sara', 'Tina', 'Vera',
        'Alice', 'Carol', 'Clara', 'Diana', 'Emily', 'Grace', 'Hazel', 'Helen', 'Julia', 'Karen',
        'Laura', 'Linda', 'Megan', 'Nancy', 'Sarah', 'Amanda', 'Hannah', 'Judith', 'Rachel', 'Rebecca',
    ];

    /** @var non-empty-list<non-empty-string> */
    private const array LAST_NAMES = [
        'Fox', 'Fry', 'Kim', 'Lee', 'Ray', 'Bell', 'Bond', 'Bush', 'Cole', 'Cook',
        'Dean', 'Ford', 'Gray', 'Hall', 'Hill', 'Hunt', 'King', 'Lane', 'Long', 'Moss',
        'Page', 'Park', 'Pope', 'Reed', 'Ross', 'Ryan', 'Shaw', 'Todd', 'Ward', 'Webb',
        'West', 'Wolf', 'Wood', 'Adams', 'Allen', 'Baker', 'Bates', 'Black', 'Brown', 'Burns',
        'Chase', 'Clark', 'Cross', 'Davis', 'Doyle', 'Evans', 'Field', 'Frost', 'Green', 'Hayes',
        'Hicks', 'Jones', 'Lewis', 'Lloyd', 'Marsh', 'Moore', 'Morse', 'Owens', 'Perry', 'Price',
        'Quinn', 'Reese', 'Scott', 'Sharp', 'Smith', 'Stone', 'Swift', 'Walsh', 'Watts', 'Wells',
        'White', 'Young', 'Barnes', 'Bishop', 'Butler', 'Carter', 'Cooper', 'Fisher', 'Foster', 'Graham',
        'Harris', 'Hunter', 'Martin', 'Miller', 'Morgan', 'Murphy', 'Palmer', 'Parker', 'Porter', 'Powell',
        'Reeves', 'Rhodes', 'Turner', 'Walker', 'Watson', 'Wilson', 'Bennett', 'Collins', 'Freeman', 'Sullivan',
    ];

    private function __construct()
    {
        // Static data holder; not instantiable.
    }

    public static function create(): Dataset
    {
        return new Dataset(
            locale: 'en',
            maleFirstNames: self::MALE_FIRST_NAMES,
            femaleFirstNames: self::FEMALE_FIRST_NAMES,
            maleLastNames: self::LAST_NAMES,
            femaleLastNames: self::LAST_NAMES,
        );
    }
}
