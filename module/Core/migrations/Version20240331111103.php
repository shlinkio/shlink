<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create a new orphan_visits_counts that will work similarly to the short_url_visits_counts
 */
final class Version20240331111103 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->skipIf($schema->hasTable('orphan_visits_counts'));

        $table = $schema->createTable('orphan_visits_counts');
        $table->addColumn('id', Types::BIGINT, [
            'unsigned' => true,
            'autoincrement' => true,
            'notnull' => true,
        ]);
        $table->setPrimaryKey(['id']);

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

        $table->addUniqueIndex(['potential_bot', 'slot_id'], 'UQ_slot');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(! $schema->hasTable('orphan_visits_counts'));
        $schema->dropTable('orphan_visits_counts');
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
