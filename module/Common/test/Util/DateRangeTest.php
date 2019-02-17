<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Util;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\DateRange;

class DateRangeTest extends TestCase
{
    /** @test */
    public function defaultConstructorSetDatesToNull()
    {
        $range = new DateRange();
        $this->assertNull($range->getStartDate());
        $this->assertNull($range->getEndDate());
        $this->assertTrue($range->isEmpty());
    }

    /** @test */
    public function providedDatesAreSet()
    {
        $startDate = Chronos::now();
        $endDate = Chronos::now();
        $range = new DateRange($startDate, $endDate);
        $this->assertSame($startDate, $range->getStartDate());
        $this->assertSame($endDate, $range->getEndDate());
        $this->assertFalse($range->isEmpty());
    }

    /**
     * @test
     * @dataProvider provideDates
     */
    public function isConsideredEmptyOnlyIfNoneOfTheDatesIsSet(
        ?Chronos $startDate,
        ?Chronos $endDate,
        bool $isEmpty
    ): void {
        $range = new DateRange($startDate, $endDate);
        $this->assertEquals($isEmpty, $range->isEmpty());
    }

    public function provideDates(): iterable
    {
        yield 'both are null' => [null, null, true];
        yield 'start is null' => [null, Chronos::now(), false];
        yield 'end is null' => [Chronos::now(), null, false];
        yield 'none are null' => [Chronos::now(), Chronos::now(), false];
    }
}
