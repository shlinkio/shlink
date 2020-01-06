<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20200105165647 extends AbstractMigration
{
    private const COLUMNS = ['lat' => 'latitude', 'lon' => 'longitude'];

    public function preUp(Schema $schema): void
    {
        foreach (self::COLUMNS as $columnName) {
            $qb = $this->connection->createQueryBuilder();
            $qb->update('visit_locations')
               ->set($columnName, '"0"')
               ->where($columnName . '=""')
               ->orWhere($columnName . ' IS NULL')
               ->execute();
        }
    }

    /**
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');

        foreach (self::COLUMNS as $newName => $oldName) {
            $visitLocations->addColumn($newName, Types::FLOAT);
        }
    }

    public function postUp(Schema $schema): void
    {
        foreach (self::COLUMNS as $newName => $oldName) {
            $qb = $this->connection->createQueryBuilder();
            $qb->update('visit_locations')
               ->set($newName, $oldName)
               ->execute();
        }
    }

    public function preDown(Schema $schema): void
    {
        foreach (self::COLUMNS as $newName => $oldName) {
            $qb = $this->connection->createQueryBuilder();
            $qb->update('visit_locations')
               ->set($oldName, $newName)
               ->execute();
        }
    }

    /**
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');

        foreach (self::COLUMNS as $colName => $oldName) {
            $visitLocations->dropColumn($colName);
        }
    }
}
