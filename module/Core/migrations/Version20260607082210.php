<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

use function hash;
use function hex2bin;

/**
 * Populates the long_url_hash column for every short URL, based on their original_url column
 */
final class Version20260607082210 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('id', 'original_url')
            ->from('short_urls');

        $result = $qb->executeQuery();
        while ($row = $result->fetchAssociative()) {
            $updateQb = $this->connection->createQueryBuilder();
            $updateQb
                ->update('short_urls')
                ->set('long_url_hash', ':binHash')
                ->where($updateQb->expr()->eq('id', ':id'))
                ->setParameters([
                    'id' => $row['id'],
                    'binHash' => hex2bin(hash('sha256', $row['original_url'])),
                ])
                ->setMaxResults(1)
                ->executeStatement();
        }
    }

    public function isTransactional(): bool
    {
        return !$this->connection->getDatabasePlatform() instanceof MySQLPlatform;
    }
}
