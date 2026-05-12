<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\ShortUrl\Repository;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ExpiredShortUrlsConditions;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ExpiredShortUrlsRepository;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

class DeleteExpiredShortUrlsRepositoryTest extends DatabaseTestCase
{
    private ExpiredShortUrlsRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createRepository(ShortUrl::class, ExpiredShortUrlsRepository::class);
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
        $this->createShortUrls(2, ['validUntil' => Chronos::now()->addDays(1)]);
        $this->createShortUrls(3, ['maxVisits' => 4], visitsPerShortUrl: 2);

        // Create some short URLs with a valid date in the past
        $this->createShortUrls(3, ['validUntil' => Chronos::now()->subDays(1)]);

        // Create some short URLs which reached the max amount of visits
        $this->createShortUrls(2, ['maxVisits' => 3], visitsPerShortUrl: 3);

        // Create some short URLs with a valid date in the past which also reached the max amount of visits
        $this->createShortUrls(4, [
            'validUntil' => Chronos::now()->subDays(1),
            'maxVisits' => 3,
        ], visitsPerShortUrl: 4);

        $this->getEntityManager()->flush();

        return 5 + 2 + 3 + 3 + 2 + 4;
    }

    private function createShortUrls(int $amountOfShortUrls, array $metadata = [], int $visitsPerShortUrl = 0): void
    {
        for ($i = 0; $i < $amountOfShortUrls; $i++) {
            $shortUrl = ShortUrl::create(new ShortUrlCreation('https://shlink.io', ...$metadata));
            $this->getEntityManager()->persist($shortUrl);

            for ($j = 0; $j < $visitsPerShortUrl; $j++) {
                $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl, Visitor::empty()));
            }
        }
    }
}
