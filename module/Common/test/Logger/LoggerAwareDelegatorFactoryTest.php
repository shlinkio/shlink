<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Logger;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Log;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Logger\LoggerAwareDelegatorFactory;
use stdClass;

class LoggerAwareDelegatorFactoryTest extends TestCase
{
    /** @var LoggerAwareDelegatorFactory */
    private $delegator;
    /** @var ObjectProphecy */
    private $container;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->delegator = new LoggerAwareDelegatorFactory();
    }

    /**
     * @test
     * @dataProvider provideInstances
     */
    public function injectsLoggerOnInstanceWhenImplementingLoggerAware($instance, int $expectedCalls): void
    {
        $callback = function () use ($instance) {
            return $instance;
        };
        $getLogger = $this->container->get(Log\LoggerInterface::class)->willReturn(new Log\NullLogger());

        $result = ($this->delegator)($this->container->reveal(), '', $callback);

        $this->assertSame($instance, $result);
        $getLogger->shouldHaveBeenCalledTimes($expectedCalls);
    }

    public function provideInstances(): iterable
    {
        yield 'no logger aware' => [new stdClass(), 0];
        yield 'logger aware' => [new class implements Log\LoggerAwareInterface {
            public function setLogger(LoggerInterface $logger): void
            {
                Assert::assertInstanceOf(Log\NullLogger::class, $logger);
            }
        }, 1];
    }
}
