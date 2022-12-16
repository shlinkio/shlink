<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\Common\Doctrine\ReopeningEntityManagerInterface;
use Shlinkio\Shlink\Core\EventDispatcher\CloseDbConnectionEventListener;
use stdClass;
use Throwable;

class CloseDbConnectionEventListenerTest extends TestCase
{
    private MockObject & ReopeningEntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(ReopeningEntityManagerInterface::class);
    }

    /**
     * @test
     * @dataProvider provideWrapped
     */
    public function connectionIsOpenedBeforeAndClosedAfter(callable $wrapped, bool &$wrappedWasCalled): void
    {
        $conn = $this->createMock(Connection::class);
        $conn->expects($this->once())->method('close');

        $this->em->expects($this->once())->method('getConnection')->willReturn($conn);
        $this->em->expects($this->once())->method('close');
        $this->em->expects($this->once())->method('open');

        $eventListener = new CloseDbConnectionEventListener($this->em, $wrapped);

        try {
            ($eventListener)(new stdClass());
        } catch (Throwable) {
            // Ignore exceptions
        }

        self::assertTrue($wrappedWasCalled);
    }

    public function provideWrapped(): iterable
    {
        yield 'does not throw exception' => (static function (): array {
            $wrappedWasCalled = false;
            $wrapped = static function () use (&$wrappedWasCalled): void {
                $wrappedWasCalled = true;
            };

            return [$wrapped, &$wrappedWasCalled];
        })();
        yield 'throws exception' => (static function (): array {
            $wrappedWasCalled = false;
            $wrapped = static function () use (&$wrappedWasCalled): void {
                $wrappedWasCalled = true;
                throw new RuntimeException('Some error');
            };

            return [$wrapped, &$wrappedWasCalled];
        })();
    }
}
