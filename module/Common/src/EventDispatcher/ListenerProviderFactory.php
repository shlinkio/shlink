<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\EventDispatcher;

use Interop\Container\ContainerInterface;
use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Zend\ServiceManager\Factory\FactoryInterface;

use function Phly\EventDispatcher\lazyListener;

class ListenerProviderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $events = $config['events'] ?? [];
        $provider = new AttachableListenerProvider();

        foreach ($events as $eventName => $listeners) {
            foreach ($listeners as $listenerName) {
                $provider->listen($eventName, lazyListener($container, $listenerName));
            }
        }

        return $provider;
    }
}
