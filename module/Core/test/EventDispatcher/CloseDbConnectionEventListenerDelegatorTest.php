<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\EventDispatcher;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Common\Doctrine\ReopeningEntityManagerInterface;
use Shlinkio\Shlink\Core\EventDispatcher\CloseDbConnectionEventListenerDelegator;

class CloseDbConnectionEventListenerDelegatorTest extends TestCase
{
    private CloseDbConnectionEventListenerDelegator $delegator;
    private ObjectProphecy $container;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
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

        $em = $this->prophesize(ReopeningEntityManagerInterface::class);
        $getEm = $this->container->get('em')->willReturn($em->reveal());

        ($this->delegator)($this->container->reveal(), '', $callback);

        $this->assertTrue($callbackInvoked);
        $getEm->shouldHaveBeenCalledOnce();
    }
}
