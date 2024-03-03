<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop device_long_urls table
 */
final class Version20240227080629 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->skipIf(! $schema->hasTable('device_long_urls'));
        $schema->dropTable('device_long_urls');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf($schema->hasTable('device_long_urls'));

        $table = $schema->createTable('device_long_urls');
        $table->addColumn('id', Types::BIGINT, [
            'unsigned' => true,
            'autoincrement' => true,
            'notnull' => true,
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('device_type', Types::STRING, ['length' => 255]);
        $table->addColumn('long_url', Types::TEXT, ['length' => 2048]);
        $table->addColumn('short_url_id', Types::BIGINT, [
            'unsigned' => true,
            'notnull' => true,
        ]);

        $table->addForeignKeyConstraint('short_urls', ['short_url_id'], ['id'], [
            'onDelete' => 'CASCADE',
            'onUpdate' => 'RESTRICT',
        ]);

        $table->addUniqueIndex(['device_type', 'short_url_id'], 'UQ_device_type_per_short_url');
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
