<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

use function Functional\some;

final class Version20200105165647 extends AbstractMigration
{
    private const COLUMNS = ['lat' => 'latitude', 'lon' => 'longitude'];

    /**
     * @throws Exception
     */
    public function preUp(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');
        $this->skipIf(some(
            self::COLUMNS,
            fn (string $v, string $newColName) => $visitLocations->hasColumn($newColName),
        ), 'New columns already exist');

        foreach (self::COLUMNS as $columnName) {
            $qb = $this->connection->createQueryBuilder();
            $qb->update('visit_locations')
               ->set($columnName, ':zeroValue')
               ->where($qb->expr()->orX(
                   $qb->expr()->eq($columnName, ':emptyString'),
                   $qb->expr()->isNull($columnName),
               ))
               ->setParameters([
                   'zeroValue' => '0',
                   'emptyString' => '',
               ])
               ->executeStatement();
        }
    }

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');

        foreach (self::COLUMNS as $newName => $oldName) {
            $visitLocations->addColumn($newName, Types::FLOAT, [
                'default' => '0.0',
            ]);
        }
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        $isPostgres = $this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform;
        $castType = $isPostgres ? 'DOUBLE PRECISION' : 'DECIMAL(9,2)';

        foreach (self::COLUMNS as $newName => $oldName) {
            $qb = $this->connection->createQueryBuilder();
            $qb->update('visit_locations')
               ->set($newName, 'CAST(' . $oldName . ' AS ' . $castType . ')')
               ->executeStatement();
        }
    }

    public function preDown(Schema $schema): void
    {
        foreach (self::COLUMNS as $newName => $oldName) {
            $qb = $this->connection->createQueryBuilder();
            $qb->update('visit_locations')
               ->set($oldName, $newName)
               ->executeStatement();
        }
    }

    /**
     * @throws Exception
     */
    public function down(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');

        foreach (self::COLUMNS as $colName => $oldName) {
            $visitLocations->dropColumn($colName);
        }
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
