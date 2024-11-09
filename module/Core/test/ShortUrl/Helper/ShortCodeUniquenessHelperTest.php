<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortCodeUniquenessHelper;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;

class ShortCodeUniquenessHelperTest extends TestCase
{
    private ShortCodeUniquenessHelper $helper;
    private MockObject & ShortUrlRepositoryInterface $repo;
    private MockObject & ShortUrl $shortUrl;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(ShortUrlRepositoryInterface::class);
        $this->helper = new ShortCodeUniquenessHelper($this->repo, new UrlShortenerOptions());

        $this->shortUrl = $this->createMock(ShortUrl::class);
        $this->shortUrl->method('getShortCode')->willReturn('abc123');
    }

    #[Test, DataProvider('provideDomains')]
    public function shortCodeIsRegeneratedIfAlreadyInUse(Domain|null $domain, string|null $expectedAuthority): void
    {
        $callIndex = 0;
        $expectedCalls = 3;
        $this->repo->expects($this->exactly($expectedCalls))->method('shortCodeIsInUseWithLock')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain('abc123', $expectedAuthority),
        )->willReturnCallback(function () use (&$callIndex, $expectedCalls) {
            $callIndex++;
            return $callIndex < $expectedCalls;
        });
        $this->shortUrl->method('getDomain')->willReturn($domain);
        $this->shortUrl->expects($this->exactly($expectedCalls - 1))->method('regenerateShortCode')->with();

        $result = $this->helper->ensureShortCodeUniqueness($this->shortUrl, false);

        self::assertTrue($result);
    }

    public static function provideDomains(): iterable
    {
        yield 'no domain' => [null, null];
        yield 'domain' => [Domain::withAuthority($authority = 's.test'), $authority];
    }

    #[Test]
    public function inUseSlugReturnsError(): void
    {
        $this->repo->expects($this->once())->method('shortCodeIsInUseWithLock')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain('abc123'),
        )->willReturn(true);
        $this->shortUrl->method('getDomain')->willReturn(null);
        $this->shortUrl->expects($this->never())->method('regenerateShortCode');

        $result = $this->helper->ensureShortCodeUniqueness($this->shortUrl, true);

        self::assertFalse($result);
    }
}
