<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\DbTest;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected const ENTITIES_TO_EMPTY = [];

    /** @var EntityManagerInterface */
    private static $em;

    public static function setEntityManager(EntityManagerInterface $em): void
    {
        self::$em = $em;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return self::$em;
    }

    public function tearDown(): void
    {
        foreach (static::ENTITIES_TO_EMPTY as $entityClass) {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb->delete($entityClass, 'x');
            $qb->getQuery()->execute();
        }

        $this->getEntityManager()->clear();
    }
}
