<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the new short_url_visits_counts table that will track visit counts per short URL
 */
final class Version20240306132518 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->skipIf($schema->hasTable('short_url_visits_counts'));

        $table = $schema->createTable('short_url_visits_counts');
        $table->addColumn('id', Types::BIGINT, [
            'unsigned' => true,
            'autoincrement' => true,
            'notnull' => true,
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('short_url_id', Types::BIGINT, [
            'unsigned' => true,
            'notnull' => true,
        ]);
        $table->addForeignKeyConstraint('short_urls', ['short_url_id'], ['id'], [
            'onDelete' => 'CASCADE',
            'onUpdate' => 'RESTRICT',
        ]);

        $table->addColumn('potential_bot', Types::BOOLEAN, ['default' => false]);

        $table->addColumn('slot_id', Types::INTEGER, [
            'unsigned' => true,
            'notnull' => true,
            'default' => 1,
        ]);

        $table->addColumn('count', Types::BIGINT, [
            'unsigned' => true,
            'notnull' => true,
            'default' => 1,
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(! $schema->hasTable('short_url_visits_counts'));
        $schema->dropTable('short_url_visits_counts');
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
