<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Listener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

use function rand;

final class OrphanVisitsCountTracker
{
    /** @var object[] */
    private array $entitiesToBeCreated = [];

    public function onFlush(OnFlushEventArgs $args): void
    {
        // Track entities that are going to be created during this flush operation
        $this->entitiesToBeCreated = $args->getObjectManager()->getUnitOfWork()->getScheduledEntityInsertions();
    }

    /**
     * @throws Exception
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $entitiesToBeCreated = $this->entitiesToBeCreated;

        // Reset tracked entities until next flush operation
        $this->entitiesToBeCreated = [];

        foreach ($entitiesToBeCreated as $entity) {
            $this->trackVisitCount($em, $entity);
        }
    }

    /**
     * @throws Exception
     */
    private function trackVisitCount(EntityManagerInterface $em, object $entity): void
    {
        // This is not an orphan visit
        if (! $entity instanceof Visit || ! $entity->isOrphan()) {
            return;
        }
        $visit = $entity;

        $isBot = $visit->potentialBot;
        $conn = $em->getConnection();
        $platformClass = $conn->getDatabasePlatform();

        match (true) {
            $platformClass instanceof PostgreSQLPlatform => $this->incrementForPostgres($conn, $isBot),
            $platformClass instanceof SQLitePlatform || $platformClass instanceof SQLServerPlatform
                => $this->incrementForOthers($conn, $isBot),
            default => $this->incrementForMySQL($conn, $isBot),
        };
    }

    /**
     * @throws Exception
     */
    private function incrementForMySQL(Connection $conn, bool $potentialBot): void
    {
        $this->incrementWithPreparedStatement($conn, $potentialBot, <<<QUERY
            INSERT INTO orphan_visits_counts (potential_bot, slot_id, count)
            VALUES (:potential_bot, RAND() * 100, 1)
            ON DUPLICATE KEY UPDATE count = count + 1;
            QUERY);
    }

    /**
     * @throws Exception
     */
    private function incrementForPostgres(Connection $conn, bool $potentialBot): void
    {
        $this->incrementWithPreparedStatement($conn, $potentialBot, <<<QUERY
            INSERT INTO orphan_visits_counts (potential_bot, slot_id, count)
            VALUES (:potential_bot, random() * 100, 1)
            ON CONFLICT (potential_bot, slot_id) DO UPDATE
              SET count = orphan_visits_counts.count + 1;
            QUERY);
    }

    /**
     * @throws Exception
     */
    private function incrementWithPreparedStatement(Connection $conn, bool $potentialBot, string $query): void
    {
        $statement = $conn->prepare($query);
        $statement->bindValue('potential_bot', $potentialBot ? 1 : 0);
        $statement->executeStatement();
    }

    /**
     * @throws Exception
     */
    private function incrementForOthers(Connection $conn, bool $potentialBot): void
    {
        $slotId = rand(1, 100);

        // For engines without a specific UPSERT syntax, do a regular locked select followed by an insert or update
        $qb = $conn->createQueryBuilder();
        $qb->select('id')
           ->from('orphan_visits_counts')
           ->where($qb->expr()->and(
               $qb->expr()->eq('potential_bot', ':potential_bot'),
               $qb->expr()->eq('slot_id', ':slot_id'),
           ))
           ->setParameter('potential_bot', $potentialBot ? '1' : '0')
           ->setParameter('slot_id', $slotId)
           ->setMaxResults(1);

        if ($conn->getDatabasePlatform()::class === SQLServerPlatform::class) {
            $qb->forUpdate();
        }

        $visitsCountId = $qb->executeQuery()->fetchOne();

        $writeQb = ! $visitsCountId
            ? $conn->createQueryBuilder()
                ->insert('orphan_visits_counts')
                ->values([
                    'potential_bot' => ':potential_bot',
                    'slot_id' => ':slot_id',
                ])
                ->setParameter('potential_bot', $potentialBot ? '1' : '0')
                ->setParameter('slot_id', $slotId)
            : $conn->createQueryBuilder()
                ->update('orphan_visits_counts')
                ->set('count', 'count + 1')
                ->where($qb->expr()->eq('id', ':visits_count_id'))
                ->setParameter('visits_count_id', $visitsCountId);

        $writeQb->executeStatement();
    }
}
