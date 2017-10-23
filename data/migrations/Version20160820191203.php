<?php
declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160820191203 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
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

    protected function createTagsTable(Schema $schema)
    {
        $table = $schema->createTable('tags');
        $table->addColumn('id', Type::BIGINT, [
            'unsigned' => true,
            'autoincrement' => true,
            'notnull' => true,
        ]);
        $table->addColumn('name', Type::STRING, [
            'length' => 255,
            'notnull' => true,
        ]);
        $table->addUniqueIndex(['name']);

        $table->setPrimaryKey(['id']);
    }

    protected function createShortUrlsInTagsTable(Schema $schema)
    {
        $table = $schema->createTable('short_urls_in_tags');
        $table->addColumn('short_url_id', Type::BIGINT, [
            'unsigned' => true,
            'notnull' => true,
        ]);
        $table->addColumn('tag_id', Type::BIGINT, [
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

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('short_urls_in_tags');
        $schema->dropTable('tags');
    }
}
