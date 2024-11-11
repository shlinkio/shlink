<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Visit\Listener;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Core\Visit\Entity\OrphanVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Repository\OrphanVisitsCountRepository;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function array_filter;
use function array_values;

class OrphanVisitsCountTrackerTest extends DatabaseTestCase
{
    private OrphanVisitsCountRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(OrphanVisitsCount::class);
    }

    #[Test]
    public function createsNewEntriesWhenNoneExist(): void
    {
        $visit = Visit::forBasePath(Visitor::empty());
        $this->getEntityManager()->persist($visit);
        $this->getEntityManager()->flush();

        /** @var OrphanVisitsCount[] $result */
        $result = $this->repo->findAll();

        self::assertCount(1, $result);
        self::assertEquals('1', $result[0]->count);
        self::assertGreaterThanOrEqual(0, $result[0]->slotId);
        self::assertLessThanOrEqual(100, $result[0]->slotId);
    }

    #[Test]
    public function editsExistingEntriesWhenAlreadyExist(): void
    {
        for ($i = 0; $i <= 100; $i++) {
            $this->getEntityManager()->persist(new OrphanVisitsCount(slotId: $i));
        }
        $this->getEntityManager()->flush();

        $visit = Visit::forRegularNotFound(Visitor::empty());
        $this->getEntityManager()->persist($visit);
        $this->getEntityManager()->flush();

        // Clear entity manager to force it to get fresh data from the database
        // This is needed because the tracker inserts natively, bypassing the entity manager
        $this->getEntityManager()->clear();

        /** @var OrphanVisitsCount[] $result */
        $result = $this->repo->findAll();
        $itemsWithCountBiggerThanOnce = array_values(array_filter(
            $result,
            static fn (OrphanVisitsCount $item) => ((int) $item->count) > 1,
        ));

        self::assertCount(101, $result);
        self::assertCount(1, $itemsWithCountBiggerThanOnce);
        self::assertEquals('2', $itemsWithCountBiggerThanOnce[0]->count);
    }
}
