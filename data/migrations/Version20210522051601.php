<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20210522051601 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        $this->skipIf($shortUrls->hasColumn('crawlable'));
        $shortUrls->addColumn('crawlable', Types::BOOLEAN, ['default' => false]);
    }

    public function down(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        $this->skipIf(! $shortUrls->hasColumn('crawlable'));
        $shortUrls->dropColumn('crawlable');
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
