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

final class ShortUrlVisitsCountTracker
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
        // This is not a visit
        if (!$entity instanceof Visit) {
            return;
        }
        $visit = $entity;

        // The short URL is not persisted yet or this is an orphan visit
        $shortUrlId = $visit->shortUrl?->getId();
        if ($shortUrlId === null || $shortUrlId === '') {
            return;
        }

        $isBot = $visit->potentialBot;
        $conn = $em->getConnection();
        $platformClass = $conn->getDatabasePlatform();

        match ($platformClass::class) {
            PostgreSQLPlatform::class => $this->incrementForPostgres($conn, $shortUrlId, $isBot),
            SQLitePlatform::class, SQLServerPlatform::class => $this->incrementForOthers($conn, $shortUrlId, $isBot),
            default => $this->incrementForMySQL($conn, $shortUrlId, $isBot),
        };
    }

    /**
     * @throws Exception
     */
    private function incrementForMySQL(Connection $conn, string $shortUrlId, bool $potentialBot): void
    {
        $this->incrementWithPreparedStatement($conn, $shortUrlId, $potentialBot, <<<QUERY
            INSERT INTO short_url_visits_counts (short_url_id, potential_bot, slot_id, count)
            VALUES (:short_url_id, :potential_bot, RAND() * 100, 1)
            ON DUPLICATE KEY UPDATE count = count + 1;
            QUERY);
    }

    /**
     * @throws Exception
     */
    private function incrementForPostgres(Connection $conn, string $shortUrlId, bool $potentialBot): void
    {
        $this->incrementWithPreparedStatement($conn, $shortUrlId, $potentialBot, <<<QUERY
            INSERT INTO short_url_visits_counts (short_url_id, potential_bot, slot_id, count)
            VALUES (:short_url_id, :potential_bot, random() * 100, 1)
            ON CONFLICT (short_url_id, potential_bot, slot_id) DO UPDATE
              SET count = short_url_visits_counts.count + 1;
            QUERY);
    }

    /**
     * @throws Exception
     */
    private function incrementWithPreparedStatement(
        Connection $conn,
        string $shortUrlId,
        bool $potentialBot,
        string $query,
    ): void {
        $statement = $conn->prepare($query);
        $statement->bindValue('short_url_id', $shortUrlId);
        $statement->bindValue('potential_bot', $potentialBot ? 1 : 0);
        $statement->executeStatement();
    }

    /**
     * @throws Exception
     */
    private function incrementForOthers(Connection $conn, string $shortUrlId, bool $potentialBot): void
    {
        $slotId = rand(1, 100);

        // For engines without a specific UPSERT syntax, do a regular locked select followed by an insert or update
        $qb = $conn->createQueryBuilder();
        $qb->select('id')
           ->from('short_url_visits_counts')
           ->where($qb->expr()->and(
               $qb->expr()->eq('short_url_id', ':short_url_id'),
               $qb->expr()->eq('potential_bot', ':potential_bot'),
               $qb->expr()->eq('slot_id', ':slot_id'),
           ))
           ->setParameter('short_url_id', $shortUrlId)
           ->setParameter('potential_bot', $potentialBot ? '1' : '0')
           ->setParameter('slot_id', $slotId)
           ->setMaxResults(1);

        if ($conn->getDatabasePlatform()::class === SQLServerPlatform::class) {
            $qb->forUpdate();
        }

        $resultSet = $qb->executeQuery()->fetchOne();
        $writeQb = ! $resultSet
            ? $conn->createQueryBuilder()
                ->insert('short_url_visits_counts')
                ->values([
                    'short_url_id' => ':short_url_id',
                    'potential_bot' => ':potential_bot',
                    'slot_id' => ':slot_id',
                ])
            : $conn->createQueryBuilder()
                   ->update('short_url_visits_counts')
                   ->set('count', 'count + 1')
                   ->where($qb->expr()->and(
                       $qb->expr()->eq('short_url_id', ':short_url_id'),
                       $qb->expr()->eq('potential_bot', ':potential_bot'),
                       $qb->expr()->eq('slot_id', ':slot_id'),
                   ));

        $writeQb->setParameter('short_url_id', $shortUrlId)
                ->setParameter('potential_bot', $potentialBot ? '1' : '0')
                ->setParameter('slot_id', $slotId)
                ->executeStatement();
    }
}
