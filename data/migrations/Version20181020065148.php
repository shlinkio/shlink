<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181020065148 extends AbstractMigration
{
    private const CAMEL_CASE_COLUMNS = [
        'countryCode',
        'countryName',
        'regionName',
        'cityName',
    ];

    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');

        foreach (self::CAMEL_CASE_COLUMNS as $name) {
            if ($visitLocations->hasColumn($name)) {
                $visitLocations->dropColumn($name);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // No down
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
