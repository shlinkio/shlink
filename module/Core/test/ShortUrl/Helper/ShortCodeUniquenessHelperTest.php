<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortCodeUniquenessHelper;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;

class ShortCodeUniquenessHelperTest extends TestCase
{
    private ShortCodeUniquenessHelper $helper;
    private MockObject & EntityManagerInterface $em;
    private MockObject & ShortUrl $shortUrl;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->helper = new ShortCodeUniquenessHelper($this->em, new UrlShortenerOptions());

        $this->shortUrl = $this->createMock(ShortUrl::class);
        $this->shortUrl->method('getShortCode')->willReturn('abc123');
    }

    /**
     * @test
     * @dataProvider provideDomains
     */
    public function shortCodeIsRegeneratedIfAlreadyInUse(?Domain $domain, ?string $expectedAuthority): void
    {
        $callIndex = 0;
        $expectedCalls = 3;
        $repo = $this->createMock(ShortUrlRepository::class);
        $repo->expects($this->exactly($expectedCalls))->method('shortCodeIsInUseWithLock')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain('abc123', $expectedAuthority),
        )->willReturnCallback(function () use (&$callIndex, $expectedCalls) {
            $callIndex++;
            return $callIndex < $expectedCalls;
        });
        $this->em->expects($this->exactly($expectedCalls))->method('getRepository')->with(ShortUrl::class)->willReturn(
            $repo,
        );
        $this->shortUrl->method('getDomain')->willReturn($domain);
        $this->shortUrl->expects($this->exactly($expectedCalls - 1))->method('regenerateShortCode')->with();

        $result = $this->helper->ensureShortCodeUniqueness($this->shortUrl, false);

        self::assertTrue($result);
    }

    public function provideDomains(): iterable
    {
        yield 'no domain' => [null, null];
        yield 'domain' => [Domain::withAuthority($authority = 's.test'), $authority];
    }

    /** @test */
    public function inUseSlugReturnsError(): void
    {
        $repo = $this->createMock(ShortUrlRepository::class);
        $repo->expects($this->once())->method('shortCodeIsInUseWithLock')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain('abc123'),
        )->willReturn(true);
        $this->em->expects($this->once())->method('getRepository')->with(ShortUrl::class)->willReturn($repo);
        $this->shortUrl->method('getDomain')->willReturn(null);
        $this->shortUrl->expects($this->never())->method('regenerateShortCode');

        $result = $this->helper->ensureShortCodeUniqueness($this->shortUrl, true);

        self::assertFalse($result);
    }
}
