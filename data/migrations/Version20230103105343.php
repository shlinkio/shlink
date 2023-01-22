<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20230103105343 extends AbstractMigration
{
    private const TABLE_NAME = 'device_long_urls';

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

        $table->addColumn('device_type', Types::STRING, ['length' => 255]);
        $table->addColumn('long_url', Types::STRING, ['length' => 2048]);
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
