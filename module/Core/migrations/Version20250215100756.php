<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix an incorrectly generated unique index in Microsoft SQL, on short_urls table, for short_code + domain_id columns.
 * The index was generated only for rows where both columns were not null, which is not the desired behavior, as
 * domain_id can be null.
 * This is due to a bug in doctrine/dbal: https://github.com/doctrine/dbal/issues/3671
 *
 * FIXME DO NOT DELETE THIS MIGRATION! IT IS NOT POSSIBLE TO DO THIS IN ENTITY CONFIG CODE WHILE THE BUG EXISTS
 */
final class Version20250215100756 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->skipIf(! $this->isMicrosoftSql());

        // Drop the existing unique index
        $shortUrls = $schema->getTable('short_urls');
        $shortUrls->dropIndex('unique_short_code_plus_domain');
    }

    public function postUp(Schema $schema): void
    {
        // The only way to get the index properly generated is by hardcoding the SQL.
        // Since this migration is run Microsoft SQL only, it is safe to use this approach.
        $this->connection->executeStatement(
            'CREATE UNIQUE INDEX unique_short_code_plus_domain ON short_urls (short_code, domain_id);',
        );
    }

    private function isMicrosoftSql(): bool
    {
        return $this->connection->getDatabasePlatform() instanceof SQLServerPlatform;
    }
}
