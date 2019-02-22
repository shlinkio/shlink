<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Paginator;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Paginator\ImplicitLoopPaginator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;
use function Functional\map;
use function range;

class ImplicitLoopPaginatorTest extends TestCase
{
    private const TOTAL_ITEMS = 10;

    /** @var Paginator */
    private $paginator;

    protected function setUp(): void
    {
        $this->paginator = new Paginator(new ArrayAdapter(range(1, self::TOTAL_ITEMS)));
    }

    /**
     * @test
     * @dataProvider provideItemsPerPage
     */
    public function allElementsAreIteratedRegardlessThePageSize(int $itemsPerPage): void
    {
        $this->paginator->setItemCountPerPage($itemsPerPage);
        $implicitLoopPaginator = new ImplicitLoopPaginator($this->paginator);

        $iteratedItems = 0;
        foreach ($implicitLoopPaginator as $item) {
            $iteratedItems++;
        }

        $this->assertEquals(self::TOTAL_ITEMS, $iteratedItems);
    }

    public function provideItemsPerPage(): iterable
    {
        return map(range(1, 20), function (int $i) {
            return [$i];
        });
    }

    /** @test */
    public function valuesWrappedInPaginatorAreProperlyParsed(): void
    {
        $valueParser = function (int $item) {
            return $item * 3;
        };
        $this->paginator->setItemCountPerPage(5);
        $implicitLoopPaginator = new ImplicitLoopPaginator($this->paginator, $valueParser);

        $items = [];
        foreach ($implicitLoopPaginator as $item) {
            $items[] = $item;
        }

        $this->assertEquals([3, 6, 9, 12, 15, 18, 21, 24, 27, 30], $items);
    }
}
