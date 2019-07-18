<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\EventDispatcher;

use Interop\Container\ContainerInterface;
use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Swoole\Http\Server as HttpServer;
use Zend\ServiceManager\Factory\FactoryInterface;

use function Phly\EventDispatcher\lazyListener;
use function Shlinkio\Shlink\Common\asyncListener;

class ListenerProviderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $events = $config['events'] ?? [];
        $provider = new AttachableListenerProvider();

        $this->registerListeners($events['regular'] ?? [], $container, $provider);
        $this->registerListeners($events['async'] ?? [], $container, $provider, true);

        return $provider;
    }

    private function registerListeners(
        array $events,
        ContainerInterface $container,
        AttachableListenerProvider $provider,
        bool $isAsync = false
    ): void {
        // Avoid registering async event listeners when the swoole server is not registered
        if ($isAsync && ! $container->has(HttpServer::class)) {
            return;
        }

        foreach ($events as $eventName => $listeners) {
            foreach ($listeners as $listenerName) {
                $eventListener = $isAsync
                    ? asyncListener($container->get(HttpServer::class), $listenerName)
                    : lazyListener($container, $listenerName);

                $provider->listen($eventName, $eventListener);
            }
        }
    }
}
