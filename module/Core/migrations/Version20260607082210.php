<?php

declare(strict_types=1);

namespace ShlinkMigrations;

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
        $shortUrlsResult = $qb->executeQuery();

        $iteration = 0;
        $this->connection->beginTransaction();

        while ($row = $shortUrlsResult->fetchAssociative()) {
            // Every few updates, commit the transaction and begin a new one
            if (($iteration % 2000) === 0) {
                $this->connection->commit();
                $this->connection->beginTransaction();
            }
            $iteration++;

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

        // Commit any pending update that is still pending
        $this->connection->commit();
    }

    public function isTransactional(): bool
    {
        // This migration should not be transactional, as it handles internal batched transactions
        return false;
    }
}
