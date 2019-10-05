<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160819142757 extends AbstractMigration
{
    private const MYSQL = 'mysql';
    private const SQLITE = 'sqlite';

    /**
     * @throws DBALException
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $db = $this->connection->getDatabasePlatform()->getName();
        $table = $schema->getTable('short_urls');
        $column = $table->getColumn('short_code');

        if ($db === self::MYSQL) {
            $column->setPlatformOption('collation', 'utf8_bin');
        } elseif ($db === self::SQLITE) {
            $column->setPlatformOption('collate', 'BINARY');
        }
    }

    /**
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $db = $this->connection->getDatabasePlatform()->getName();
    }
}
