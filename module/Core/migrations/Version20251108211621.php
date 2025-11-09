<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add expired_short_url_redirect column to domains table
 */
final class Version20251108211621 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $domains = $schema->getTable('domains');

        if ($domains->hasColumn('expired_short_url_redirect')) {
            return;
        }

        $domains->addColumn('expired_short_url_redirect', Types::TEXT, [
            'length' => 2048,
            'notnull' => false,
            'default' => null,
        ]);
    }

    public function down(Schema $schema): void
    {
        $domains = $schema->getTable('domains');

        if (! $domains->hasColumn('expired_short_url_redirect')) {
            return;
        }

        $domains->dropColumn('expired_short_url_redirect');
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
