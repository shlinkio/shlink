<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Visit\Repository;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Visit\Entity\OrphanVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\ShortUrlVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Repository\OrphanVisitsCountRepository;
use Shlinkio\Shlink\Core\Visit\Repository\ShortUrlVisitsCountRepository;
use Shlinkio\Shlink\Core\Visit\Repository\VisitDeleterRepository;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

class VisitDeleterRepositoryTest extends DatabaseTestCase
{
    private VisitDeleterRepository $repo;
    private ShortUrlVisitsCountRepository $visitsCountRepo;
    private OrphanVisitsCountRepository $orphanVisitsCountRepo;

    protected function setUp(): void
    {
        $this->repo = $this->createRepository(Visit::class, VisitDeleterRepository::class);
        $this->visitsCountRepo = $this->getEntityManager()->getRepository(ShortUrlVisitsCount::class);
        $this->orphanVisitsCountRepo = $this->getEntityManager()->getRepository(OrphanVisitsCount::class);
    }

    #[Test]
    public function deletesExpectedShortUrlVisits(): void
    {
        $shortUrl1 = ShortUrl::withLongUrl('https://foo.com');
        $this->getEntityManager()->persist($shortUrl1);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl1, Visitor::empty()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl1, Visitor::empty()));

        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData([
            ShortUrlInputFilter::LONG_URL => 'https://foo.com',
            ShortUrlInputFilter::DOMAIN => 's.test',
            ShortUrlInputFilter::CUSTOM_SLUG => 'foo',
        ]), new PersistenceShortUrlRelationResolver($this->getEntityManager()));
        $this->getEntityManager()->persist($shortUrl2);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl2, Visitor::empty()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl2, Visitor::empty()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl2, Visitor::empty()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl2, Visitor::empty()));

        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData([
            ShortUrlInputFilter::LONG_URL => 'https://foo.com',
            ShortUrlInputFilter::CUSTOM_SLUG => 'foo',
        ]), new PersistenceShortUrlRelationResolver($this->getEntityManager()));
        $this->getEntityManager()->persist($shortUrl3);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl3, Visitor::empty()));

        $this->getEntityManager()->flush();

        $this->assertVisitsDeletionForShortUrl($shortUrl1, 2);
        $this->assertVisitsDeletionForShortUrl($shortUrl2, 4);
        $this->assertVisitsDeletionForShortUrl($shortUrl3, 1);
    }

    private function assertVisitsDeletionForShortUrl(ShortUrl $shortUrl, int $expectedDeleteCount): void
    {
        // There should be at least one visit count before deletion
        self::assertGreaterThan(0, $this->visitsCountRepo->count(['shortUrl' => $shortUrl]));
        self::assertEquals($expectedDeleteCount, $this->repo->deleteShortUrlVisits($shortUrl));

        // Visits counts are also deleted, and trying to delete again results in no visits deleted
        self::assertEquals(0, $this->visitsCountRepo->count(['shortUrl' => $shortUrl]));
        self::assertEquals(0, $this->repo->deleteShortUrlVisits($shortUrl));
    }

    #[Test]
    public function deletesExpectedOrphanVisits(): void
    {
        $visitor = Visitor::empty();
        $this->getEntityManager()->persist(Visit::forBasePath($visitor));
        $this->getEntityManager()->persist(Visit::forInvalidShortUrl($visitor));
        $this->getEntityManager()->persist(Visit::forRegularNotFound($visitor));
        $this->getEntityManager()->persist(Visit::forBasePath($visitor));
        $this->getEntityManager()->persist(Visit::forInvalidShortUrl($visitor));
        $this->getEntityManager()->persist(Visit::forRegularNotFound($visitor));

        $this->getEntityManager()->flush();

        self::assertGreaterThan(0, $this->orphanVisitsCountRepo->count());
        self::assertEquals(6, $this->repo->deleteOrphanVisits());
        self::assertEquals(0, $this->repo->deleteOrphanVisits());
        self::assertEquals(0, $this->orphanVisitsCountRepo->count());
    }
}
