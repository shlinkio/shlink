<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\EventDispatcher;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Common\EventDispatcher\SwooleEventDispatcher;
use stdClass;

class SwooleEventDispatcherTest extends TestCase
{
    /** @var ObjectProphecy */
    private $innerDispatcher;

    public function setUp(): void
    {
        $this->innerDispatcher = $this->prophesize(EventDispatcherInterface::class);
    }

    /**
     * @test
     * @dataProvider provideIsSwoole
     */
    public function callsInnerDispatcherOnlyWhenInSwooleContext(bool $isSwoole, int $expectedCalls): void
    {
        $dispatcher = new SwooleEventDispatcher($this->innerDispatcher->reveal(), $isSwoole);
        $event = new stdClass();

        $dispatcher->dispatch($event);

        $this->innerDispatcher->dispatch($event)->shouldHaveBeenCalledTimes($expectedCalls);
    }

    public function provideIsSwoole(): iterable
    {
        yield 'with swoole' => [true, 1];
        yield 'without swoole' => [false, 0];
    }
}
