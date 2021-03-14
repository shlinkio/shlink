<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181020060559 extends AbstractMigration
{
    private const COLUMNS = [
        'countryCode' => 'country_code',
        'countryName' => 'country_name',
        'regionName' => 'region_name',
        'cityName' => 'city_name',
    ];

    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $this->createColumns($schema->getTable('visit_locations'), self::COLUMNS);
    }

    private function createColumns(Table $visitLocations, array $columnNames): void
    {
        foreach ($columnNames as $name) {
            if (! $visitLocations->hasColumn($name)) {
                $visitLocations->addColumn($name, Types::STRING, ['notnull' => false]);
            }
        }
    }

    /**
     * @throws SchemaException
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');

        // If the camel case columns do not exist, do nothing
        if (! $visitLocations->hasColumn('countryCode')) {
            return;
        }

        $qb = $this->connection->createQueryBuilder();
        $qb->update('visit_locations');
        foreach (self::COLUMNS as $camelCaseName => $snakeCaseName) {
            $qb->set($snakeCaseName, $camelCaseName);
        }
        $qb->execute();
    }

    public function down(Schema $schema): void
    {
        // No down
    }
}
