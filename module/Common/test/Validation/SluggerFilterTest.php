<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Validation;

use Cocur\Slugify\SlugifyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Validation\SluggerFilter;

class SluggerFilterTest extends TestCase
{
    /** @var SluggerFilter */
    private $filter;
    /** @var ObjectProphecy */
    private $slugger;

    public function setUp(): void
    {
        $this->slugger = $this->prophesize(SlugifyInterface::class);
        $this->filter = new SluggerFilter($this->slugger->reveal());
    }

    /**
     * @test
     * @dataProvider provideValuesToFilter
     */
    public function providedValueIsFilteredAsExpected($providedValue, $expectedValue): void
    {
        $slugify = $this->slugger->slugify($providedValue)->willReturn('slug');

        $result = $this->filter->filter($providedValue);

        $this->assertEquals($expectedValue, $result);
        $slugify->shouldHaveBeenCalledTimes($expectedValue !== null ? 1 : 0);
    }

    public function provideValuesToFilter(): iterable
    {
        yield 'null' => [null, null];
        yield 'empty string' => ['', null];
        yield 'not empty string' => ['foo', 'slug'];
    }
}
