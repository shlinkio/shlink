<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20200106215144 extends AbstractMigration
{
    private const COLUMNS = ['latitude', 'longitude'];

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $visitLocations = $schema->getTable('visit_locations');
        $this->skipIf($this->oldColumnsDoNotExist($visitLocations), 'Old columns do not exist');

        foreach (self::COLUMNS as $colName) {
            $visitLocations->dropColumn($colName);
        }
    }

    public function oldColumnsDoNotExist(Table $visitLocations): bool
    {
        foreach (self::COLUMNS as $oldColName) {
            if ($visitLocations->hasColumn($oldColName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws Exception
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

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
