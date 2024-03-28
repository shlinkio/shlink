<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Visit\Listener;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\ShortUrlVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function array_filter;
use function array_values;

class ShortUrlVisitsCountTrackerTest extends DatabaseTestCase
{
    private EntityRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(ShortUrlVisitsCount::class);
    }

    #[Test]
    public function createsNewEntriesWhenNoneExist(): void
    {
        $shortUrl = ShortUrl::createFake();
        $this->getEntityManager()->persist($shortUrl);

        $visit = Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance());
        $this->getEntityManager()->persist($visit);
        $this->getEntityManager()->flush();

        /** @var ShortUrlVisitsCount[] $result */
        $result = $this->repo->findBy(['shortUrl' => $shortUrl]);

        self::assertCount(1, $result);
        self::assertEquals('1', $result[0]->count);
        self::assertGreaterThanOrEqual(0, $result[0]->slotId);
        self::assertLessThanOrEqual(100, $result[0]->slotId);
    }

    #[Test]
    public function editsExistingEntriesWhenAlreadyExist(): void
    {
        $shortUrl = ShortUrl::createFake();
        $this->getEntityManager()->persist($shortUrl);

        for ($i = 0; $i <= 100; $i++) {
            $this->getEntityManager()->persist(new ShortUrlVisitsCount($shortUrl, slotId: $i));
        }
        $this->getEntityManager()->flush();

        $visit = Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance());
        $this->getEntityManager()->persist($visit);
        $this->getEntityManager()->flush();

        // Clear entity manager to force it to get fresh data from the database
        // This is needed because the tracker inserts natively, bypassing the entity manager
        $this->getEntityManager()->clear();

        /** @var ShortUrlVisitsCount[] $result */
        $result = $this->repo->findBy(['shortUrl' => $shortUrl]);
        $itemsWithCountBiggerThanOnce = array_values(array_filter(
            $result,
            static fn (ShortUrlVisitsCount $item) => ((int) $item->count) > 1,
        ));

        self::assertCount(101, $result);
        self::assertCount(1, $itemsWithCountBiggerThanOnce);
        self::assertEquals('2', $itemsWithCountBiggerThanOnce[0]->count);
    }
}
