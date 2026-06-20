<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
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
        do {
            $resultsFound = $this->processBatch();
        } while ($resultsFound);
    }

    public function processBatch(): bool
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('id', 'original_url')
            ->from('short_urls')
            // If this migration times out, this will ensure it can be rerun, and it will continue where it was left, so
            // it can be run multiple times until all short URLs have been processed
            ->where($qb->expr()->eq('long_url_hash', ':longUrlHash'))
            ->setParameters(['longUrlHash' => ''])
            ->setMaxResults(10_000);
        $shortUrlsResult = $qb->executeQuery();

        return $this->connection->transactional(function () use ($shortUrlsResult) {
            $resultsFound = false;

            while ($row = $shortUrlsResult->fetchAssociative()) {
                $resultsFound = true;

                $updateQb = $this->connection->createQueryBuilder();
                $updateQb
                    ->update('short_urls')
                    ->set('long_url_hash', ':binHash')
                    ->where($updateQb->expr()->eq('id', ':id'))
                    ->setParameters([
                        'id' => $row['id'],
                        'binHash' => hex2bin(hash('sha256', $row['original_url'])),
                    ], [
                        'binHash' => Types::BINARY,
                    ])
                    ->setMaxResults(1)
                    ->executeStatement();
            }

            return $resultsFound;
        });
    }

    public function isTransactional(): bool
    {
        // This migration should not be transactional, as it handles internal batched transactions
        return false;
    }
}
