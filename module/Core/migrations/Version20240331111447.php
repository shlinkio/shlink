<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240331111447 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $visitsQb = $this->connection->createQueryBuilder();
        $visitsQb->select('COUNT(id)')
                 ->from('visits')
                 ->where($visitsQb->expr()->isNull('short_url_id'))
                 ->andWhere($visitsQb->expr()->eq('potential_bot', ':potential_bot'));

        $botsCount = $visitsQb->setParameter('potential_bot', '1')->executeQuery()->fetchOne();
        $nonBotsCount = $visitsQb->setParameter('potential_bot', '0')->executeQuery()->fetchOne();

        if ($botsCount > 0) {
            $this->insertCount($botsCount, potentialBot: true);
        }
        if ($nonBotsCount > 0) {
            $this->insertCount($nonBotsCount, potentialBot: false);
        }
    }

    private function insertCount(string|int $count, bool $potentialBot): void
    {
        $this->connection->createQueryBuilder()
            ->insert('orphan_visits_counts')
            ->values([
                'count' => ':count',
                'potential_bot' => ':potential_bot',
            ])
            ->setParameters([
                'count' => $count,
                'potential_bot' => $potentialBot ? '1' : '0',
            ])
            ->executeStatement();
    }
}
