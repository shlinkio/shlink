<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20200106215144 extends AbstractMigration
{
    private const COLUMNS = ['latitude', 'longitude'];

    /**
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');

        foreach (self::COLUMNS as $colName) {
            $visitLocations->dropColumn($colName);
        }
    }

    /**
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');

        foreach (self::COLUMNS as $colName) {
            $visitLocations->addColumn($colName, Types::STRING, [
                'notnull' => false,
            ]);
        }
    }
}
