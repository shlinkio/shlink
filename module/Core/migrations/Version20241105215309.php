<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

use function hash;

/**
 * Hash API keys as SHA256
 */
final class Version20241105215309 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $keyColumnName = $this->connection->quoteIdentifier('key');

        $qb = $this->connection->createQueryBuilder();
        $qb->select($keyColumnName)
           ->from('api_keys');
        $result = $qb->executeQuery();

        $updateQb = $this->connection->createQueryBuilder();
        $updateQb
            ->update('api_keys')
            ->set($keyColumnName, ':encryptedKey')
            ->where($updateQb->expr()->eq($keyColumnName, ':plainTextKey'));

        while ($key = $result->fetchOne()) {
            $updateQb->setParameters([
                'encryptedKey' => hash('sha256', $key),
                'plainTextKey' => $key,
            ])->executeStatement();
        }
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
