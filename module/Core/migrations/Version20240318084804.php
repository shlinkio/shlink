<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create initial entries in the short_url_visits_counts table for existing visits
 */
final class Version20240318084804 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $qb = $this->connection->createQueryBuilder();
        $result = $qb->select('id')
                     ->from('short_urls')
                     ->executeQuery();

        while ($shortUrlId = $result->fetchOne()) {
            $visitsQb = $this->connection->createQueryBuilder();
            $visitsQb->select('COUNT(id)')
                     ->from('visits')
                     ->where($visitsQb->expr()->eq('short_url_id', ':short_url_id'))
                     ->andWhere($visitsQb->expr()->eq('potential_bot', ':potential_bot'))
                     ->setParameter('short_url_id', $shortUrlId);

            $botsCount = $visitsQb->setParameter('potential_bot', '1')->executeQuery()->fetchOne();
            $nonBotsCount = $visitsQb->setParameter('potential_bot', '0')->executeQuery()->fetchOne();

            $this->connection->createQueryBuilder()
                 ->insert('short_url_visits_counts')
                 ->values([
                     'short_url_id' => ':short_url_id',
                     'count' => ':count',
                     'potential_bot' => '1',
                 ])
                 ->setParameters([
                     'short_url_id' => $shortUrlId,
                     'count' => $botsCount,
                 ])
                 ->executeStatement();
            $this->connection->createQueryBuilder()
                 ->insert('short_url_visits_counts')
                 ->values([
                     'short_url_id' => ':short_url_id',
                     'count' => ':count',
                     'potential_bot' => '0',
                 ])
                 ->setParameters([
                     'short_url_id' => $shortUrlId,
                     'count' => $nonBotsCount,
                 ])
                 ->executeStatement();
        }
    }
}
