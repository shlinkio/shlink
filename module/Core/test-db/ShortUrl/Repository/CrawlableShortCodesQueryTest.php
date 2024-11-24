<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\ShortUrl\Repository;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Repository\CrawlableShortCodesQuery;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

class CrawlableShortCodesQueryTest extends DatabaseTestCase
{
    private CrawlableShortCodesQuery $query;

    protected function setUp(): void
    {
        $this->query = $this->createRepository(ShortUrl::class, CrawlableShortCodesQuery::class);
    }

    #[Test]
    public function invokingQueryReturnsExpectedResult(): void
    {
        $createShortUrl = fn (bool $crawlable) => ShortUrl::create(
            ShortUrlCreation::fromRawData(['crawlable' => $crawlable, 'longUrl' => 'https://foo.com']),
        );

        $shortUrl1 = $createShortUrl(true);
        $this->getEntityManager()->persist($shortUrl1);
        $shortUrl2 = $createShortUrl(false);
        $this->getEntityManager()->persist($shortUrl2);
        $shortUrl3 = $createShortUrl(true);
        $this->getEntityManager()->persist($shortUrl3);
        $shortUrl4 = $createShortUrl(true);
        $this->getEntityManager()->persist($shortUrl4);
        $shortUrl5 = $createShortUrl(false);
        $this->getEntityManager()->persist($shortUrl5);
        $this->getEntityManager()->flush();

        $results = [...($this->query)()];

        self::assertCount(3, $results);
        self::assertContains($shortUrl1->getShortCode(), $results);
        self::assertContains($shortUrl3->getShortCode(), $results);
        self::assertContains($shortUrl4->getShortCode(), $results);
        self::assertNotContains($shortUrl2->getShortCode(), $results);
        self::assertNotContains($shortUrl5->getShortCode(), $results);
    }
}
