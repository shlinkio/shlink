<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Util;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\Core\Util\DoctrineBatchHelper;

class DoctrineBatchHelperTest extends TestCase
{
    private DoctrineBatchHelper $helper;
    private MockObject & EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->helper = new DoctrineBatchHelper($this->em);
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
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('commit');
        $this->em->expects($this->never())->method('rollback');
        $this->em->expects($this->exactly($expectedCalls))->method('flush');
        $this->em->expects($this->exactly($expectedCalls))->method('clear');

        $wrappedIterable = $this->helper->wrapIterable($iterable, $batchSize);

        foreach ($wrappedIterable as $item) {
            // Iterable needs to be iterated for the logic to be invoked
        }
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
        $this->em->expects($this->once())->method('flush')->willThrowException(new RuntimeException());
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->never())->method('commit');
        $this->em->expects($this->once())->method('rollback');

        $wrappedIterable = $this->helper->wrapIterable([1, 2, 3], 1);

        self::expectException(RuntimeException::class);

        foreach ($wrappedIterable as $item) {
            // Iterable needs to be iterated for the logic to be invoked
        }
    }
}
