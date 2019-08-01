<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Migrations;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Symfony\Component\Lock\Factory as Locker;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

use function stripos;

class LockMigrationsEntityManagerDelegator implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name, callable $callback, ?array $options = null)
    {
        /** @var EntityManager $em */
        $em = $callback();
        $isMigrations = stripos($_SERVER['SCRIPT_NAME'], 'doctrine-migrations') !== false;
        if (! $isMigrations) {
            return $em;
        }

        $locker = $container->get(Locker::class);
        $em->getConnection()->getEventManager()->addEventSubscriber(new LockMigrationsSubscriber($locker));
        return $em;
    }
}
