<?php

namespace ShlinkMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160819142757 extends AbstractMigration
{
    const MYSQL = 'mysql';
    const SQLITE = 'sqlite';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $db = $this->connection->getDatabasePlatform()->getName();
        $table = $schema->getTable('short_urls');
        $column = $table->getColumn('short_code');

        if ($db === self::MYSQL) {
            $column->setPlatformOption('collation', 'utf8_bin');
        } elseif ($db === self::SQLITE) {
            $column->setPlatformOption('collation', 'BINARY');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $db = $this->connection->getDatabasePlatform()->getName();
    }
}
