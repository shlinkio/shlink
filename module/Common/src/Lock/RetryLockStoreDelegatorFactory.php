<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Lock;

use Interop\Container\ContainerInterface;
use Symfony\Component\Lock\Store\RetryTillSaveStore;
use Symfony\Component\Lock\StoreInterface;

class RetryLockStoreDelegatorFactory
{
    public function __invoke(ContainerInterface $container, $name, callable $callback): RetryTillSaveStore
    {
        /** @var StoreInterface $originalStore */
        $originalStore = $callback();
        return new RetryTillSaveStore($originalStore);
    }
}
