<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241125213106 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->skipIf(! $this->isMsSql());

        // Recreate unique_short_code_plus_domain index in Microsoft SQL, as it accidentally has the columns defined in
        // the wrong order after Version20230130090946 migration
        $shortUrls = $schema->getTable('short_urls');
        $shortUrls->dropIndex('unique_short_code_plus_domain');
        $shortUrls->addUniqueIndex(['short_code', 'domain_id'], 'unique_short_code_plus_domain');
    }

    private function isMsSql(): bool
    {
        return $this->connection->getDatabasePlatform() instanceof SQLServerPlatform;
    }
}
