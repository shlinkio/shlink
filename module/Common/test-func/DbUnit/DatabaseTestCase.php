<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\DbUnit;

use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\DbUnit\Database\Connection as DbConn;
use PHPUnit\DbUnit\DataSet\IDataSet as DataSet;
use PHPUnit\DbUnit\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    const ENTITIES_TO_EMPTY = [];

    /**
     * @var EntityManagerInterface
     */
    public static $em;
    /**
     * @var DbConn
     */
    private static $conn;

    public function getConnection(): DbConn
    {
        if (isset(self::$conn)) {
            return self::$conn;
        }

        /** @var PDOConnection $pdo */
        $pdo = static::$em->getConnection()->getWrappedConnection();
        return self::$conn = $this->createDefaultDBConnection($pdo, static::$em->getConnection()->getDatabase());
    }

    public function getDataSet(): DataSet
    {
        return $this->createArrayDataSet([]);
    }

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
