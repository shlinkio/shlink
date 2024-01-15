<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230127145327 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        $shortUrls->modifyColumn('original_url', [
            'length' => 4096,
        ]);
    }

    public function down(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        $shortUrls->modifyColumn('original_url', [
            'length' => 2048,
        ]);
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
