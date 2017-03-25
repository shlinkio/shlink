<?php
namespace ShlinkioTest\Shlink\Common\Util;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\DateRange;

class DateRangeTest extends TestCase
{
    /**
     * @test
     */
    public function defaultConstructorSetDatesToNull()
    {
        $range = new DateRange();
        $this->assertNull($range->getStartDate());
        $this->assertNull($range->getEndDate());
        $this->assertTrue($range->isEmpty());
    }

    /**
     * @test
     */
    public function providedDatesAreSet()
    {
        $startDate = new \DateTime();
        $endDate = new \DateTime();
        $range = new DateRange($startDate, $endDate);
        $this->assertSame($startDate, $range->getStartDate());
        $this->assertSame($endDate, $range->getEndDate());
        $this->assertFalse($range->isEmpty());
    }
}
