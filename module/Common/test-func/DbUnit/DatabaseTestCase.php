<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\DbUnit;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected const ENTITIES_TO_EMPTY = [];

    /** @var EntityManagerInterface */
    public static $em;

    protected function getEntityManager(): EntityManagerInterface
    {
        return static::$em;
    }

    public function tearDown()
    {
        // Empty all entity tables defined by this test after each test
        foreach (static::ENTITIES_TO_EMPTY as $entityClass) {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb->delete($entityClass, 'x');
            $qb->getQuery()->execute();
        }

        // Clear entity manager
        $this->getEntityManager()->clear();
    }
}
