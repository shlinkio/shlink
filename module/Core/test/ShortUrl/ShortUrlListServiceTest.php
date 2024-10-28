<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlListRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlListService;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use ShlinkioTest\Shlink\Core\Util\ApiKeyDataProviders;

use function count;

class ShortUrlListServiceTest extends TestCase
{
    private ShortUrlListService $service;
    private MockObject & ShortUrlListRepositoryInterface $repo;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(ShortUrlListRepositoryInterface::class);
        $this->service = new ShortUrlListService($this->repo, new UrlShortenerOptions());
    }

    #[Test, DataProviderExternal(ApiKeyDataProviders::class, 'adminApiKeysProvider')]
    public function listedUrlsAreReturnedFromEntityManager(?ApiKey $apiKey): void
    {
        $list = [
            ShortUrl::createFake(),
            ShortUrl::createFake(),
            ShortUrl::createFake(),
            ShortUrl::createFake(),
        ];

        $this->repo->expects($this->once())->method('findList')->willReturn($list);
        $this->repo->expects($this->once())->method('countList')->willReturn(count($list));

        $paginator = $this->service->listShortUrls(ShortUrlsParams::empty(), $apiKey);

        self::assertCount(4, $paginator);
        self::assertCount(4, $paginator->getCurrentPageResults());
    }
}
