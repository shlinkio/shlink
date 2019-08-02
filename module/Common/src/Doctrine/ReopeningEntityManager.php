<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Doctrine;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;

class ReopeningEntityManager extends EntityManagerDecorator
{
    /** @var callable */
    private $emFactory;

    public function __construct(EntityManagerInterface $wrapped, callable $emFactory)
    {
        parent::__construct($wrapped);
        $this->emFactory = $emFactory;
    }

    protected function getWrappedEntityManager(): EntityManagerInterface
    {
        if (! $this->wrapped->isOpen()) {
            $this->wrapped = ($this->emFactory)(
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
