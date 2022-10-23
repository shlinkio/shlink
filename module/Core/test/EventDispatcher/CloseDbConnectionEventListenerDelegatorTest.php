<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Common\Doctrine\ReopeningEntityManagerInterface;
use Shlinkio\Shlink\Core\EventDispatcher\CloseDbConnectionEventListenerDelegator;

class CloseDbConnectionEventListenerDelegatorTest extends TestCase
{
    private CloseDbConnectionEventListenerDelegator $delegator;
    private MockObject $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->delegator = new CloseDbConnectionEventListenerDelegator();
    }

    /** @test */
    public function properDependenciesArePassed(): void
    {
        $callbackInvoked = false;
        $callback = function () use (&$callbackInvoked): callable {
            $callbackInvoked = true;

            return function (): void {
            };
        };

        $this->container->expects($this->once())->method('get')->with('em')->willReturn(
            $this->createMock(ReopeningEntityManagerInterface::class),
        );

        ($this->delegator)($this->container, '', $callback);

        self::assertTrue($callbackInvoked);
    }
}
