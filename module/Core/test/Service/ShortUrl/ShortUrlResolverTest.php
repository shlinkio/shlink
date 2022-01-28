<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service\ShortUrl;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolver;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use ShlinkioTest\Shlink\Core\Util\ApiKeyHelpersTrait;

use function Functional\map;
use function range;

class ShortUrlResolverTest extends TestCase
{
    use ApiKeyHelpersTrait;
    use ProphecyTrait;

    private ShortUrlResolver $urlResolver;
    private ObjectProphecy $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->urlResolver = new ShortUrlResolver($this->em->reveal());
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function shortCodeIsProperlyParsed(?ApiKey $apiKey): void
    {
        $shortUrl = ShortUrl::withLongUrl('expected_url');
        $shortCode = $shortUrl->getShortCode();
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode);

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $findOne = $repo->findOne($identifier, $apiKey?->spec())->willReturn($shortUrl);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $result = $this->urlResolver->resolveShortUrl($identifier, $apiKey);

        self::assertSame($shortUrl, $result);
        $findOne->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function exceptionIsThrownIfShortcodeIsNotFound(?ApiKey $apiKey): void
    {
        $shortCode = 'abc123';
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode);

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $findOne = $repo->findOne($identifier, $apiKey?->spec())->willReturn(null);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal(), $apiKey);

        $this->expectException(ShortUrlNotFoundException::class);
        $findOne->shouldBeCalledOnce();
        $getRepo->shouldBeCalledOnce();

        $this->urlResolver->resolveShortUrl($identifier, $apiKey);
    }

    /** @test */
    public function shortCodeToEnabledShortUrlProperlyParsesShortCode(): void
    {
        $shortUrl = ShortUrl::withLongUrl('expected_url');
        $shortCode = $shortUrl->getShortCode();

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $findOneByShortCode = $repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
        )->willReturn($shortUrl);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $result = $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode));

        self::assertSame($shortUrl, $result);
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
        $findOneByShortCode = $repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
        )->willReturn($shortUrl);
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
            $shortUrl = ShortUrl::fromMeta(ShortUrlMeta::fromRawData(['maxVisits' => 3, 'longUrl' => '']));
            $shortUrl->setVisits(new ArrayCollection(map(
                range(0, 4),
                fn () => Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance()),
            )));

            return $shortUrl;
        })()];
        yield 'future validSince' => [ShortUrl::fromMeta(ShortUrlMeta::fromRawData(
            ['validSince' => $now->addMonth()->toAtomString(), 'longUrl' => ''],
        ))];
        yield 'past validUntil' => [ShortUrl::fromMeta(ShortUrlMeta::fromRawData(
            ['validUntil' => $now->subMonth()->toAtomString(), 'longUrl' => ''],
        ))];
        yield 'mixed' => [(function () use ($now) {
            $shortUrl = ShortUrl::fromMeta(ShortUrlMeta::fromRawData([
                'maxVisits' => 3,
                'validUntil' => $now->subMonth()->toAtomString(),
                'longUrl' => '',
            ]));
            $shortUrl->setVisits(new ArrayCollection(map(
                range(0, 4),
                fn () => Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance()),
            )));

            return $shortUrl;
        })()];
    }
}
