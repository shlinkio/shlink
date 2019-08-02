<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Doctrine;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManager;

class ReopeningEntityManager extends EntityManagerDecorator
{
    protected function getWrappedEntityManager(): EntityManager
    {
        if (! $this->wrapped->isOpen()) {
            $this->wrapped= EntityManager::create(
                $this->wrapped->getConnection(),
                $this->wrapped->getConfiguration(),
                $this->wrapped->getEventManager()
            );
        }

        return $this->wrapped;
    }

    public function flush($entity = null): void
    {
        $this->getWrappedEntityManager()->flush($entity);
    }

    public function persist($object): void
    {
        $this->getWrappedEntityManager()->persist($object);
    }

    public function remove($object): void
    {
        $this->getWrappedEntityManager()->remove($object);
    }

    public function refresh($object): void
    {
        $this->getWrappedEntityManager()->refresh($object);
    }

    public function merge($object)
    {
        return $this->getWrappedEntityManager()->merge($object);
    }
}
