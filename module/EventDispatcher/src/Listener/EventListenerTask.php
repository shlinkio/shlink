<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\EventDispatcher\Listener;

use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\EventDispatcher\Async\TaskInterface;

use function get_class;
use function sprintf;

class EventListenerTask implements TaskInterface
{
    /** @var string */
    private $listenerName;
    /** @var object */
    private $event;

    public function __construct(string $listenerName, object $event)
    {
        $this->listenerName = $listenerName;
        $this->event = $event;
    }

    public function run(ContainerInterface $container): void
    {
        ($container->get($this->listenerName))($this->event);
    }

    public function toString(): string
    {
        return sprintf('Listener -> "%s", Event -> "%s"', $this->listenerName, get_class($this->event));
    }
}
