<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\EventDispatcher;

use Psr\Container\ContainerInterface;

use function get_class;
use function sprintf;

class Task
{
    /** @var string */
    private $regularListenerName;
    /** @var object */
    private $event;

    public function __construct(string $regularListenerName, object $event)
    {
        $this->regularListenerName = $regularListenerName;
        $this->event = $event;
    }

    public function __invoke(ContainerInterface $container)
    {
        ($container->get($this->regularListenerName))($this->event);
    }

    public function toString(): string
    {
        return sprintf('Listener -> "%s", Event -> "%s"', $this->regularListenerName, get_class($this->event));
    }
}
