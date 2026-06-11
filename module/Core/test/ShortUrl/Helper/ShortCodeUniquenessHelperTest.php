<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortCodeUniquenessHelper;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;

class ShortCodeUniquenessHelperTest extends TestCase
{
    private ShortCodeUniquenessHelper $helper;
    private MockObject&ShortUrlRepositoryInterface $repo;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(ShortUrlRepositoryInterface::class);
        $this->helper = new ShortCodeUniquenessHelper($this->repo, new UrlShortenerOptions());
    }

    #[Test, DataProvider('provideDomains')]
    public function shortCodeIsRegeneratedIfAlreadyInUse(string|null $domain, string|null $expectedAuthority): void
    {
        $shortUrl = $this->shortUrl($domain);
        $initialShortCode = $shortUrl->shortCode;

        $callIndex = 0;
        $expectedCalls = 3;
        $this->repo
            ->expects($this->exactly($expectedCalls))
            ->method('shortCodeIsInUseWithLock')
            ->with($this->callback(
                static fn (ShortUrlIdentifier $identifier) => $identifier->domain === $expectedAuthority,
            ))
            ->willReturnCallback(static function () use (&$callIndex, $expectedCalls) {
                $callIndex++;
                return $callIndex < $expectedCalls;
            });

        $result = $this->helper->ensureShortCodeUniqueness($shortUrl, hasCustomSlug: false);

        self::assertTrue($result);
        self::assertNotEquals($initialShortCode, $shortUrl->shortCode);
    }

    public static function provideDomains(): iterable
    {
        yield 'no domain' => [null, null];
        yield 'domain' => [$authority = 's.test', $authority];
    }

    #[Test]
    public function inUseSlugReturnsError(): void
    {
        $this->repo
            ->expects($this->once())
            ->method('shortCodeIsInUseWithLock')
            ->willReturn(true);

        $shortUrl = $this->shortUrl();
        $initialShortCode = $shortUrl->shortCode;
        $result = $this->helper->ensureShortCodeUniqueness($shortUrl, hasCustomSlug: true);

        self::assertFalse($result);
        self::assertEquals($initialShortCode, $shortUrl->shortCode);
    }

    private function shortUrl(string|null $domain = null): ShortUrl
    {
        return ShortUrl::create(new ShortUrlCreation('https://example.com', domain: $domain));
    }
}
