<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160820191203 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Check if the tables already exist
        $tables = $schema->getTables();
        foreach ($tables as $table) {
            if ($table->getName() === 'tags') {
                return;
            }
        }

        $this->createTagsTable($schema);
        $this->createShortUrlsInTagsTable($schema);
    }

    private function createTagsTable(Schema $schema): void
    {
        $table = $schema->createTable('tags');
        $table->addColumn('id', Types::BIGINT, [
            'unsigned' => true,
            'autoincrement' => true,
            'notnull' => true,
        ]);
        $table->addColumn('name', Types::STRING, [
            'length' => 255,
            'notnull' => true,
        ]);
        $table->addUniqueIndex(['name']);

        $table->setPrimaryKey(['id']);
    }

    private function createShortUrlsInTagsTable(Schema $schema): void
    {
        $table = $schema->createTable('short_urls_in_tags');
        $table->addColumn('short_url_id', Types::BIGINT, [
            'unsigned' => true,
            'notnull' => true,
        ]);
        $table->addColumn('tag_id', Types::BIGINT, [
            'unsigned' => true,
            'notnull' => true,
        ]);

        $table->addForeignKeyConstraint('tags', ['tag_id'], ['id'], [
            'onDelete' => 'CASCADE',
            'onUpdate' => 'RESTRICT',
        ]);
        $table->addForeignKeyConstraint('short_urls', ['short_url_id'], ['id'], [
            'onDelete' => 'CASCADE',
            'onUpdate' => 'RESTRICT',
        ]);

        $table->setPrimaryKey(['short_url_id', 'tag_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('short_urls_in_tags');
        $schema->dropTable('tags');
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
