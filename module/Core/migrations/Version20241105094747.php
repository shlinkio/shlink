<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * In preparation to start hashing API keys, move all plain-text keys to the `name` column for all keys without name,
 * and append it to the name for all keys which already have a name.
 */
final class Version20241105094747 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $keyColumnName = $this->connection->quoteIdentifier('key');

        // Append key to the name for all API keys that already have a name
        $qb = $this->connection->createQueryBuilder();
        $qb->update('api_keys')
            ->set('name', 'CONCAT(name, ' . $this->connection->quote(' - ') . ', ' . $keyColumnName . ')')
            ->where($qb->expr()->isNotNull('name'));
        $qb->executeStatement();

        // Set plain key as name for all API keys without a name
        $qb = $this->connection->createQueryBuilder();
        $qb->update('api_keys')
           ->set('name', $keyColumnName)
           ->where($qb->expr()->isNull('name'));
        $qb->executeStatement();
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
