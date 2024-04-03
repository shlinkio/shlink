<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\ShortUrl\Repository;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ExpiredShortUrlsConditions;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ExpiredShortUrlsRepository;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

class DeleteExpiredShortUrlsRepositoryTest extends DatabaseTestCase
{
    private ExpiredShortUrlsRepository $repository;

    protected function setUp(): void
    {
        $em = $this->getEntityManager();
        $this->repository = new ExpiredShortUrlsRepository($em, $em->getClassMetadata(ShortUrl::class));
    }

    #[Test]
    #[TestWith([new ExpiredShortUrlsConditions(pastValidUntil: false, maxVisitsReached: false), 0])]
    #[TestWith([new ExpiredShortUrlsConditions(pastValidUntil: true, maxVisitsReached: false), 7])]
    #[TestWith([new ExpiredShortUrlsConditions(pastValidUntil: false, maxVisitsReached: true), 6])]
    #[TestWith([new ExpiredShortUrlsConditions(pastValidUntil: true, maxVisitsReached: true), 9])]
    public function deletesExpectedAmountOfShortUrls(
        ExpiredShortUrlsConditions $conditions,
        int $expectedDeletedShortUrls,
    ): void {
        $createdShortUrls = $this->createDataSet();

        self::assertEquals($expectedDeletedShortUrls, $this->repository->delete($conditions));
        self::assertEquals(
            $createdShortUrls - $expectedDeletedShortUrls,
            $this->getEntityManager()->getRepository(ShortUrl::class)->count(),
        );
    }

    #[Test]
    #[TestWith([new ExpiredShortUrlsConditions(pastValidUntil: false, maxVisitsReached: false), 0])]
    #[TestWith([new ExpiredShortUrlsConditions(pastValidUntil: true, maxVisitsReached: false), 7])]
    #[TestWith([new ExpiredShortUrlsConditions(pastValidUntil: false, maxVisitsReached: true), 6])]
    #[TestWith([new ExpiredShortUrlsConditions(pastValidUntil: true, maxVisitsReached: true), 9])]
    public function countsExpectedAmountOfShortUrls(
        ExpiredShortUrlsConditions $conditions,
        int $expectedShortUrlsCount,
    ): void {
        $createdShortUrls = $this->createDataSet();

        self::assertEquals($expectedShortUrlsCount, $this->repository->dryCount($conditions));
        self::assertEquals($createdShortUrls, $this->getEntityManager()->getRepository(ShortUrl::class)->count());
    }

    private function createDataSet(): int
    {
        // Create some non-expired short URLs
        $this->createShortUrls(5);
        $this->createShortUrls(2, [ShortUrlInputFilter::VALID_UNTIL => Chronos::now()->addDays(1)->toAtomString()]);
        $this->createShortUrls(3, [ShortUrlInputFilter::MAX_VISITS => 4], visitsPerShortUrl: 2);

        // Create some short URLs with a valid date in the past
        $this->createShortUrls(3, [ShortUrlInputFilter::VALID_UNTIL => Chronos::now()->subDays(1)->toAtomString()]);

        // Create some short URLs which reached the max amount of visits
        $this->createShortUrls(2, [ShortUrlInputFilter::MAX_VISITS => 3], visitsPerShortUrl: 3);

        // Create some short URLs with a valid date in the past which also reached the max amount of visits
        $this->createShortUrls(4, [
            ShortUrlInputFilter::VALID_UNTIL => Chronos::now()->subDays(1)->toAtomString(),
            ShortUrlInputFilter::MAX_VISITS => 3,
        ], visitsPerShortUrl: 4);

        $this->getEntityManager()->flush();

        return 5 + 2 + 3 + 3 + 2 + 4;
    }

    private function createShortUrls(int $amountOfShortUrls, array $metadata = [], int $visitsPerShortUrl = 0): void
    {
        for ($i = 0; $i < $amountOfShortUrls; $i++) {
            $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
                ShortUrlInputFilter::LONG_URL => 'https://shlink.io',
                ...$metadata,
            ]));
            $this->getEntityManager()->persist($shortUrl);

            for ($j = 0; $j < $visitsPerShortUrl; $j++) {
                $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance()));
            }
        }
    }
}
