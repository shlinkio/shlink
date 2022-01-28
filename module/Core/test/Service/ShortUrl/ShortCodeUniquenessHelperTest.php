<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortCodeUniquenessHelper;

class ShortCodeUniquenessHelperTest extends TestCase
{
    use ProphecyTrait;

    private ShortCodeUniquenessHelper $helper;
    private ObjectProphecy $em;
    private ObjectProphecy $shortUrl;

    protected function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->helper = new ShortCodeUniquenessHelper($this->em->reveal());

        $this->shortUrl = $this->prophesize(ShortUrl::class);
        $this->shortUrl->getShortCode()->willReturn('abc123');
    }

    /**
     * @test
     * @dataProvider provideDomains
     */
    public function shortCodeIsRegeneratedIfAlreadyInUse(?Domain $domain, ?string $expectedAuthority): void
    {
        $callIndex = 0;
        $expectedCalls = 3;
        $repo = $this->prophesize(ShortUrlRepository::class);
        $shortCodeIsInUse = $repo->shortCodeIsInUseWithLock(
            ShortUrlIdentifier::fromShortCodeAndDomain('abc123', $expectedAuthority),
        )->will(function () use (&$callIndex, $expectedCalls) {
            $callIndex++;
            return $callIndex < $expectedCalls;
        });
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());
        $this->shortUrl->getDomain()->willReturn($domain);

        $result = $this->helper->ensureShortCodeUniqueness($this->shortUrl->reveal(), false);

        self::assertTrue($result);
        $this->shortUrl->regenerateShortCode()->shouldHaveBeenCalledTimes($expectedCalls - 1);
        $getRepo->shouldBeCalledTimes($expectedCalls);
        $shortCodeIsInUse->shouldBeCalledTimes($expectedCalls);
    }

    public function provideDomains(): iterable
    {
        yield 'no domain' => [null, null];
        yield 'domain' => [Domain::withAuthority($authority = 'doma.in'), $authority];
    }

    /** @test */
    public function inUseSlugReturnsError(): void
    {
        $repo = $this->prophesize(ShortUrlRepository::class);
        $shortCodeIsInUse = $repo->shortCodeIsInUseWithLock(
            ShortUrlIdentifier::fromShortCodeAndDomain('abc123'),
        )->willReturn(true);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());
        $this->shortUrl->getDomain()->willReturn(null);

        $result = $this->helper->ensureShortCodeUniqueness($this->shortUrl->reveal(), true);

        self::assertFalse($result);
        $this->shortUrl->regenerateShortCode()->shouldNotHaveBeenCalled();
        $getRepo->shouldBeCalledOnce();
        $shortCodeIsInUse->shouldBeCalledOnce();
    }
}
