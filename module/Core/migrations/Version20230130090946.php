<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230130090946 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->skipIf(! $this->isMsSql(), 'This only sets MsSQL-specific database options');

        $shortUrls = $schema->getTable('short_urls');
        $shortCode = $shortUrls->getColumn('short_code');
        // Drop the unique index before changing the collation, as the field is part of this index
        $shortUrls->dropIndex('unique_short_code_plus_domain');
        $shortCode->setPlatformOption('collation', 'Latin1_General_CS_AS');
    }

    public function postUp(Schema $schema): void
    {
        if ($this->isMsSql()) {
            // The index needs to be re-created in postUp, but here, we can only use statements run against the
            // connection directly
            $this->connection->executeStatement(
                'CREATE INDEX unique_short_code_plus_domain ON short_urls (domain_id, short_code);',
            );
        }
    }

    public function down(Schema $schema): void
    {
        // No down
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }

    private function isMsSql(): bool
    {
        return $this->connection->getDatabasePlatform() instanceof SQLServerPlatform;
    }
}
