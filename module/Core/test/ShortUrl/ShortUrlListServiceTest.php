<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlListRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlListService;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use ShlinkioTest\Shlink\Core\Util\ApiKeyHelpersTrait;

use function count;

class ShortUrlListServiceTest extends TestCase
{
    use ApiKeyHelpersTrait;

    private ShortUrlListService $service;
    private MockObject & ShortUrlListRepositoryInterface $repo;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(ShortUrlListRepositoryInterface::class);
        $this->service = new ShortUrlListService($this->repo, new UrlShortenerOptions());
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function listedUrlsAreReturnedFromEntityManager(?ApiKey $apiKey): void
    {
        $list = [
            ShortUrl::createEmpty(),
            ShortUrl::createEmpty(),
            ShortUrl::createEmpty(),
            ShortUrl::createEmpty(),
        ];

        $this->repo->expects($this->once())->method('findList')->willReturn($list);
        $this->repo->expects($this->once())->method('countList')->willReturn(count($list));

        $paginator = $this->service->listShortUrls(ShortUrlsParams::emptyInstance(), $apiKey);

        self::assertCount(4, $paginator);
        self::assertCount(4, $paginator->getCurrentPageResults());
    }
}
