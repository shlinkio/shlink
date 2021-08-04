<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Common\Doctrine\ReopeningEntityManagerInterface;

class CloseDbConnectionEventListenerDelegator
{
    public function __invoke(
        ContainerInterface $container,
        string $name,
        callable $callback,
    ): CloseDbConnectionEventListener {
        /** @var callable $wrapped */
        $wrapped = $callback();
        /** @var ReopeningEntityManagerInterface $em */
        $em = $container->get('em');

        return new CloseDbConnectionEventListener($em, $wrapped);
    }
}
