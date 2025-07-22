<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Make the redirect_condition match_value column nullable, so that we can support new valueless-query-param and
 * any-value-query-param conditions consistently.
 */
final class Version20250722060208 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('redirect_conditions')->getColumn('match_value')->setNotnull(false);
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
