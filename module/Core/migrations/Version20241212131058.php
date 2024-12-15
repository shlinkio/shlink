<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;

/**
 * Create a new table to track geolocation db updates
 */
final class Version20241212131058 extends AbstractMigration
{
    private const string TABLE_NAME = 'geolocation_db_updates';

    public function up(Schema $schema): void
    {
        $this->skipIf($schema->hasTable(self::TABLE_NAME));

        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('id', Types::BIGINT, [
            'unsigned' => true,
            'autoincrement' => true,
            'notnull' => true,
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('date_created', ChronosDateTimeType::CHRONOS_DATETIME, ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('date_updated', ChronosDateTimeType::CHRONOS_DATETIME, ['default' => 'CURRENT_TIMESTAMP']);

        $table->addColumn('status', Types::STRING, [
            'length' => 128,
            'default' => 'in-progress', // in-progress, success, error
        ]);
        $table->addColumn('filesystem_id', Types::STRING, ['length' => 512]);

        $table->addColumn('error', Types::STRING, [
            'length' => 1024,
            'default' => null,
            'notnull' => false,
        ]);

        // Index on date_updated, as we'll usually sort the query by this field
        $table->addIndex(['date_updated'], 'IDX_geolocation_date_updated');
        // Index on status and filesystem_id, as we'll usually filter the query by those fields
        $table->addIndex(['status', 'filesystem_id'], 'IDX_geolocation_status_filesystem');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(! $schema->hasTable(self::TABLE_NAME));
        $schema->dropTable(self::TABLE_NAME);
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
