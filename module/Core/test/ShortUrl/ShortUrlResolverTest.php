<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolver;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use ShlinkioTest\Shlink\Core\Util\ApiKeyDataProviders;

use function array_map;
use function range;

class ShortUrlResolverTest extends TestCase
{
    private ShortUrlResolver $urlResolver;
    private MockObject & ShortUrlRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(ShortUrlRepository::class);
        $this->urlResolver = new ShortUrlResolver($this->repo, new UrlShortenerOptions());
    }

    #[Test, DataProviderExternal(ApiKeyDataProviders::class, 'adminApiKeysProvider')]
    public function shortCodeIsProperlyParsed(ApiKey|null $apiKey): void
    {
        $shortUrl = ShortUrl::withLongUrl('https://expected_url');
        $shortCode = $shortUrl->getShortCode();
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode);

        $this->repo->expects($this->once())->method('findOne')->with($identifier, $apiKey?->spec())->willReturn(
            $shortUrl,
        );

        $result = $this->urlResolver->resolveShortUrl($identifier, $apiKey);

        self::assertSame($shortUrl, $result);
    }

    #[Test, DataProviderExternal(ApiKeyDataProviders::class, 'adminApiKeysProvider')]
    public function exceptionIsThrownIfShortCodeIsNotFound(ApiKey|null $apiKey): void
    {
        $shortCode = 'abc123';
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode);

        $this->repo->expects($this->once())->method('findOne')->with($identifier, $apiKey?->spec())->willReturn(null);

        $this->expectException(ShortUrlNotFoundException::class);

        $this->urlResolver->resolveShortUrl($identifier, $apiKey);
    }

    #[Test]
    public function resolveEnabledShortUrlProperlyParsesShortCode(): void
    {
        $shortUrl = ShortUrl::withLongUrl('https://expected_url');
        $shortCode = $shortUrl->getShortCode();

        $this->repo->expects($this->once())->method('findOneWithDomainFallback')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            ShortUrlMode::STRICT,
        )->willReturn($shortUrl);

        $result = $this->urlResolver->resolveEnabledShortUrl(ShortUrlIdentifier::fromShortCodeAndDomain($shortCode));

        self::assertSame($shortUrl, $result);
    }

    #[Test, DataProvider('provideResolutionMethods')]
    public function resolutionThrowsExceptionIfUrlIsNotEnabled(string $method): void
    {
        $shortCode = 'abc123';

        $this->repo->expects($this->once())->method('findOneWithDomainFallback')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            ShortUrlMode::STRICT,
        )->willReturn(null);

        $this->expectException(ShortUrlNotFoundException::class);

        $this->urlResolver->{$method}(ShortUrlIdentifier::fromShortCodeAndDomain($shortCode));
    }

    public static function provideResolutionMethods(): iterable
    {
        yield 'resolveEnabledShortUrl' => ['resolveEnabledShortUrl'];
        yield 'resolvePublicShortUrl' => ['resolvePublicShortUrl'];
    }

    #[Test, DataProvider('provideDisabledShortUrls')]
    public function resolveEnabledShortUrlThrowsExceptionIfUrlIsNotEnabled(ShortUrl $shortUrl): void
    {
        $shortCode = $shortUrl->getShortCode();

        $this->repo->expects($this->once())->method('findOneWithDomainFallback')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            ShortUrlMode::STRICT,
        )->willReturn($shortUrl);

        $this->expectException(ShortUrlNotFoundException::class);

        $this->urlResolver->resolveEnabledShortUrl(ShortUrlIdentifier::fromShortCodeAndDomain($shortCode));
    }

    public static function provideDisabledShortUrls(): iterable
    {
        $now = Chronos::now();

        yield 'maxVisits reached' => [(function () {
            $shortUrl = ShortUrl::create(
                ShortUrlCreation::fromRawData(['maxVisits' => 3, 'longUrl' => 'https://longUrl']),
            );
            $shortUrl->setVisits(new ArrayCollection(array_map(
                fn () => Visit::forValidShortUrl($shortUrl, Visitor::empty()),
                range(0, 4),
            )));

            return $shortUrl;
        })()];
        yield 'future validSince' => [ShortUrl::create(ShortUrlCreation::fromRawData(
            ['validSince' => $now->addMonths(1)->toAtomString(), 'longUrl' => 'https://longUrl'],
        ))];
        yield 'past validUntil' => [ShortUrl::create(ShortUrlCreation::fromRawData(
            ['validUntil' => $now->subMonths(1)->toAtomString(), 'longUrl' => 'https://longUrl'],
        ))];
        yield 'mixed' => [(function () use ($now) {
            $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
                'maxVisits' => 3,
                'validUntil' => $now->subMonths(1)->toAtomString(),
                'longUrl' => 'https://longUrl',
            ]));
            $shortUrl->setVisits(new ArrayCollection(array_map(
                fn () => Visit::forValidShortUrl($shortUrl, Visitor::empty()),
                range(0, 4),
            )));

            return $shortUrl;
        })()];
    }
}
