<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;

use const PHP_SAPI;

use function extension_loaded;

class SwooleEventDispatcher implements EventDispatcherInterface
{
    /** @var bool */
    private $isSwoole;
    /** @var EventDispatcherInterface */
    private $innerDispatcher;

    public function __construct(EventDispatcherInterface $innerDispatcher, ?bool $isSwoole = null)
    {
        $this->innerDispatcher = $innerDispatcher;
        $this->isSwoole = $isSwoole ?? PHP_SAPI === 'cli' && extension_loaded('swoole');
    }

    /**
     * Provide all relevant listeners with an event to process.
     *
     * @param object $event
     *   The object to process.
     *
     * @return object
     *   The Event that was passed, now modified by listeners.
     */
    public function dispatch(object $event)
    {
        // Do not really dispatch the event if the app is not being run with swoole
        if (! $this->isSwoole) {
            return $event;
        }

        return $this->innerDispatcher->dispatch($event);
    }
}
