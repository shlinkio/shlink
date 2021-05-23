<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Util;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Shlinkio\Shlink\Core\Util\DoctrineBatchHelper;

class DoctrineBatchHelperTest extends TestCase
{
    use ProphecyTrait;

    private DoctrineBatchHelper $helper;
    private ObjectProphecy $em;

    protected function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->helper = new DoctrineBatchHelper($this->em->reveal());
    }

    /**
     * @test
     * @dataProvider provideIterables
     */
    public function entityManagerIsFlushedAndClearedTheExpectedAmountOfTimes(
        array $iterable,
        int $batchSize,
        int $expectedCalls,
    ): void {
        $wrappedIterable = $this->helper->wrapIterable($iterable, $batchSize);

        foreach ($wrappedIterable as $item) {
            // Iterable needs to be iterated for the logic to be invoked
        }

        $this->em->beginTransaction()->shouldHaveBeenCalledOnce();
        $this->em->commit()->shouldHaveBeenCalledOnce();
        $this->em->rollback()->shouldNotHaveBeenCalled();
        $this->em->flush()->shouldHaveBeenCalledTimes($expectedCalls);
        $this->em->clear()->shouldHaveBeenCalledTimes($expectedCalls);
    }

    public function provideIterables(): iterable
    {
        yield [[], 100, 1];
        yield [[1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 3, 4];
        yield [[1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 11, 1];
    }

    /** @test */
    public function transactionIsRolledBackWhenAnErrorOccurs(): void
    {
        $flush = $this->em->flush()->willThrow(RuntimeException::class);

        $wrappedIterable = $this->helper->wrapIterable([1, 2, 3], 1);

        self::expectException(RuntimeException::class);
        $flush->shouldBeCalledOnce();
        $this->em->beginTransaction()->shouldBeCalledOnce();
        $this->em->commit()->shouldNotBeCalled();
        $this->em->rollback()->shouldBeCalledOnce();

        foreach ($wrappedIterable as $item) {
            // Iterable needs to be iterated for the logic to be invoked
        }
    }
}
