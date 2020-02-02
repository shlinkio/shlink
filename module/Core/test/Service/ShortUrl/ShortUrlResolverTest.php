<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service\ShortUrl;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolver;

use function Functional\map;
use function range;

class ShortUrlResolverTest extends TestCase
{
    private ShortUrlResolver $urlResolver;
    private ObjectProphecy $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->urlResolver = new ShortUrlResolver($this->em->reveal());
    }

    /** @test */
    public function shortCodeIsProperlyParsed(): void
    {
        $shortUrl = new ShortUrl('expected_url');
        $shortCode = $shortUrl->getShortCode();

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $findOne = $repo->findOne($shortCode, null)->willReturn($shortUrl);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $result = $this->urlResolver->resolveShortUrl(new ShortUrlIdentifier($shortCode));

        $this->assertSame($shortUrl, $result);
        $findOne->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function exceptionIsThrownIfShortcodeIsNotFound(): void
    {
        $shortCode = 'abc123';

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $findOne = $repo->findOne($shortCode, null)->willReturn(null);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $this->expectException(ShortUrlNotFoundException::class);
        $findOne->shouldBeCalledOnce();
        $getRepo->shouldBeCalledOnce();

        $this->urlResolver->resolveShortUrl(new ShortUrlIdentifier($shortCode));
    }

    /** @test */
    public function shortCodeToEnabledShortUrlProperlyParsesShortCode(): void
    {
        $shortUrl = new ShortUrl('expected_url');
        $shortCode = $shortUrl->getShortCode();

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $findOneByShortCode = $repo->findOneWithDomainFallback($shortCode, null)->willReturn($shortUrl);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $result = $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode));

        $this->assertSame($shortUrl, $result);
        $findOneByShortCode->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideDisabledShortUrls
     */
    public function shortCodeToEnabledShortUrlThrowsExceptionIfUrlIsNotEnabled(ShortUrl $shortUrl): void
    {
        $shortCode = $shortUrl->getShortCode();

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $findOneByShortCode = $repo->findOneWithDomainFallback($shortCode, null)->willReturn($shortUrl);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $this->expectException(ShortUrlNotFoundException::class);
        $findOneByShortCode->shouldBeCalledOnce();
        $getRepo->shouldBeCalledOnce();

        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode));
    }

    public function provideDisabledShortUrls(): iterable
    {
        $now = Chronos::now();

        yield 'maxVisits reached' => [(function () {
            $shortUrl = new ShortUrl('', ShortUrlMeta::fromRawData(['maxVisits' => 3]));
            $shortUrl->setVisits(new ArrayCollection(map(
                range(0, 4),
                fn () => new Visit($shortUrl, Visitor::emptyInstance()),
            )));

            return $shortUrl;
        })()];
        yield 'future validSince' => [new ShortUrl('', ShortUrlMeta::fromRawData([
            'validSince' => $now->addMonth()->toAtomString(),
        ]))];
        yield 'past validUntil' => [new ShortUrl('', ShortUrlMeta::fromRawData([
            'validUntil' => $now->subMonth()->toAtomString(),
        ]))];
        yield 'mixed' => [(function () use ($now) {
            $shortUrl = new ShortUrl('', ShortUrlMeta::fromRawData([
                'maxVisits' => 3,
                'validUntil' => $now->subMonth()->toAtomString(),
            ]));
            $shortUrl->setVisits(new ArrayCollection(map(
                range(0, 4),
                fn () => new Visit($shortUrl, Visitor::emptyInstance()),
            )));

            return $shortUrl;
        })()];
    }
}
