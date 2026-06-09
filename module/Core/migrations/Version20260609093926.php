<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Makes long_url_hash non-nullable, now that previous migration has set the values for all existing entries
 */
final class Version20260609093926 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        $shortUrls->getColumn('long_url_hash')->setNotnull(true);
    }

    public function isTransactional(): bool
    {
        return !$this->connection->getDatabasePlatform() instanceof MySQLPlatform;
    }
}
