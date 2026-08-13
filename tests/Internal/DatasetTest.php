<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Names\Tests\Internal;

use Rasuvaeff\PropertyTesting\Names\Gender;
use Rasuvaeff\PropertyTesting\Names\Internal\Dataset;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(Dataset::class)]
final class DatasetTest
{
    private Dataset $dataset;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->dataset = new Dataset(
            locale: 'xx',
            maleFirstNames: ['Om', 'Om2'],
            femaleFirstNames: ['Fa'],
            maleLastNames: ['Mos'],
            femaleLastNames: ['Mosa'],
            maleMiddleNames: ['Omovich'],
            femaleMiddleNames: ['Omovna'],
        );
    }

    public function returnsGenderedFirstNames(): void
    {
        Assert::same($this->dataset->firstNames(Gender::Male), ['Om', 'Om2']);
        Assert::same($this->dataset->firstNames(Gender::Female), ['Fa']);
    }

    public function mergesFirstNamesWhenGenderIsUnspecified(): void
    {
        Assert::same($this->dataset->firstNames(null), ['Om', 'Om2', 'Fa']);
    }

    public function returnsGenderedLastNames(): void
    {
        Assert::same($this->dataset->lastNames(Gender::Male), ['Mos']);
        Assert::same($this->dataset->lastNames(Gender::Female), ['Mosa']);
        Assert::same($this->dataset->lastNames(null), ['Mos', 'Mosa']);
    }

    public function returnsGenderedMiddleNames(): void
    {
        Assert::same($this->dataset->middleNames(Gender::Male), ['Omovich']);
        Assert::same($this->dataset->middleNames(Gender::Female), ['Omovna']);
        Assert::same($this->dataset->middleNames(null), ['Omovich', 'Omovna']);
    }

    public function reportsMiddleNameSupport(): void
    {
        Assert::true($this->dataset->hasMiddleNames());
        Assert::false($this->withoutMiddleNames()->hasMiddleNames());
        Assert::false($this->maleMiddleNamesOnly()->hasMiddleNames());
    }

    public function middleNamesFailWithTheLocaleInTheMessage(): void
    {
        try {
            $this->withoutMiddleNames()->middleNames(Gender::Male);
            Assert::fail('A dataset without middle names accepted the lookup');
        } catch (\InvalidArgumentException $exception) {
            Assert::same(
                $exception->getMessage(),
                'Locale "yy" has no middle-name (patronymic) dataset',
            );
        }
    }

    /**
     * A half-filled dataset must fail per gender, not silently return the one
     * list it happens to have: `middleNames(null)` would otherwise produce
     * male-only patronymics for female names.
     */
    public function middleNamesFailForTheMissingGenderOnly(): void
    {
        $dataset = $this->maleMiddleNamesOnly();

        Assert::same($dataset->middleNames(Gender::Male), ['Omovich']);
        Assert::same($dataset->middleNames(null), ['Omovich']);

        try {
            $dataset->middleNames(Gender::Female);
            Assert::fail('A dataset without female middle names accepted the lookup');
        } catch (\InvalidArgumentException $exception) {
            Assert::string($exception->getMessage())->contains('has no middle-name');
        }
    }

    private function withoutMiddleNames(): Dataset
    {
        return new Dataset(
            locale: 'yy',
            maleFirstNames: ['Om'],
            femaleFirstNames: ['Fa'],
            maleLastNames: ['Mos'],
            femaleLastNames: ['Mosa'],
        );
    }

    private function maleMiddleNamesOnly(): Dataset
    {
        return new Dataset(
            locale: 'zz',
            maleFirstNames: ['Om'],
            femaleFirstNames: ['Fa'],
            maleLastNames: ['Mos'],
            femaleLastNames: ['Mosa'],
            maleMiddleNames: ['Omovich'],
        );
    }
}
