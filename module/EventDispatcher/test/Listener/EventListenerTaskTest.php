<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\EventDispatcher\Listener;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\EventDispatcher\Listener\EventListenerTask;
use stdClass;

use function get_class;
use function sprintf;

class EventListenerTaskTest extends TestCase
{
    /** @var EventListenerTask */
    private $task;
    /** @var object */
    private $event;
    /** @var string */
    private $listenerName;

    public function setUp(): void
    {
        $this->event = new stdClass();
        $this->listenerName = 'the_listener';

        $this->task = new EventListenerTask($this->listenerName, $this->event);
    }

    /** @test */
    public function toStringReturnsTheStringRepresentation(): void
    {
        $this->assertEquals(
            sprintf('Listener -> "%s", Event -> "%s"', $this->listenerName, get_class($this->event)),
            $this->task->toString()
        );
    }

    /** @test */
    public function runInvokesContainerAndListenerWithEvent(): void
    {
        $invoked = false;
        $container = $this->prophesize(ContainerInterface::class);
        $listener = function (object $event) use (&$invoked) {
            $invoked = true;
            Assert::assertSame($event, $this->event);
        };

        $getListener = $container->get($this->listenerName)->willReturn($listener);

        $this->task->run($container->reveal());

        $this->assertTrue($invoked);
        $getListener->shouldHaveBeenCalledOnce();
    }
}
