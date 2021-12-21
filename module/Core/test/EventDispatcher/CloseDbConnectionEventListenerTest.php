<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Shlinkio\Shlink\Common\Doctrine\ReopeningEntityManagerInterface;
use Shlinkio\Shlink\Core\EventDispatcher\CloseDbConnectionEventListener;
use stdClass;
use Throwable;

class CloseDbConnectionEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(ReopeningEntityManagerInterface::class);
    }

    /**
     * @test
     * @dataProvider provideWrapped
     */
    public function connectionIsOpenedBeforeAndClosedAfter(callable $wrapped, bool &$wrappedWasCalled): void
    {
        $conn = $this->prophesize(Connection::class);
        $close = $conn->close()->will(function (): void {
        });
        $getConn = $this->em->getConnection()->willReturn($conn->reveal());
        $close = $this->em->close()->will(function (): void {
        });
        $open = $this->em->open()->will(function (): void {
        });

        $eventListener = new CloseDbConnectionEventListener($this->em->reveal(), $wrapped);

        try {
            ($eventListener)(new stdClass());
        } catch (Throwable) {
            // Ignore exceptions
        }

        self::assertTrue($wrappedWasCalled);
        $close->shouldHaveBeenCalledOnce();
        $getConn->shouldHaveBeenCalledOnce();
        $close->shouldHaveBeenCalledOnce();
        $open->shouldHaveBeenCalledOnce();
    }

    public function provideWrapped(): iterable
    {
        yield 'does not throw exception' => (function (): array {
            $wrappedWasCalled = false;
            $wrapped = function () use (&$wrappedWasCalled): void {
                $wrappedWasCalled = true;
            };

            return [$wrapped, &$wrappedWasCalled];
        })();
        yield 'throws exception' => (function (): array {
            $wrappedWasCalled = false;
            $wrapped = function () use (&$wrappedWasCalled): void {
                $wrappedWasCalled = true;
                throw new RuntimeException('Some error');
            };

            return [$wrapped, &$wrappedWasCalled];
        })();
    }
}
