<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Visit\Repository;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Repository\VisitDeleterRepository;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

class VisitDeleterRepositoryTest extends DatabaseTestCase
{
    private VisitDeleterRepository $repo;

    protected function setUp(): void
    {
        $em = $this->getEntityManager();
        $this->repo = new VisitDeleterRepository($em, $em->getClassMetadata(Visit::class));
    }

    #[Test]
    public function deletesExpectedShortUrlVisits(): void
    {
        $shortUrl1 = ShortUrl::withLongUrl('https://foo.com');
        $this->getEntityManager()->persist($shortUrl1);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl1, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl1, Visitor::emptyInstance()));

        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData([
            ShortUrlInputFilter::LONG_URL => 'https://foo.com',
            ShortUrlInputFilter::DOMAIN => 's.test',
            ShortUrlInputFilter::CUSTOM_SLUG => 'foo',
        ]), new PersistenceShortUrlRelationResolver($this->getEntityManager()));
        $this->getEntityManager()->persist($shortUrl2);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl2, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl2, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl2, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl2, Visitor::emptyInstance()));

        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData([
            ShortUrlInputFilter::LONG_URL => 'https://foo.com',
            ShortUrlInputFilter::CUSTOM_SLUG => 'foo',
        ]), new PersistenceShortUrlRelationResolver($this->getEntityManager()));
        $this->getEntityManager()->persist($shortUrl3);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl3, Visitor::emptyInstance()));

        $this->getEntityManager()->flush();

        self::assertEquals(2, $this->repo->deleteShortUrlVisits($shortUrl1));
        self::assertEquals(0, $this->repo->deleteShortUrlVisits($shortUrl1));
        self::assertEquals(4, $this->repo->deleteShortUrlVisits($shortUrl2));
        self::assertEquals(0, $this->repo->deleteShortUrlVisits($shortUrl2));
        self::assertEquals(1, $this->repo->deleteShortUrlVisits($shortUrl3));
        self::assertEquals(0, $this->repo->deleteShortUrlVisits($shortUrl3));
    }

    #[Test]
    public function deletesExpectedOrphanVisits(): void
    {
        $visitor = Visitor::emptyInstance();
        $this->getEntityManager()->persist(Visit::forBasePath($visitor));
        $this->getEntityManager()->persist(Visit::forInvalidShortUrl($visitor));
        $this->getEntityManager()->persist(Visit::forRegularNotFound($visitor));
        $this->getEntityManager()->persist(Visit::forBasePath($visitor));
        $this->getEntityManager()->persist(Visit::forInvalidShortUrl($visitor));
        $this->getEntityManager()->persist(Visit::forRegularNotFound($visitor));

        $this->getEntityManager()->flush();

        self::assertEquals(6, $this->repo->deleteOrphanVisits());
        self::assertEquals(0, $this->repo->deleteOrphanVisits());
    }
}
