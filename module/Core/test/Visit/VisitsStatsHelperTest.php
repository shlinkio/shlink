<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit;

use Doctrine\ORM\EntityManagerInterface;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepository;
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Repository\TagRepository;
use Shlinkio\Shlink\Core\Visit\Entity\OrphanVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\ShortUrlVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;
use Shlinkio\Shlink\Core\Visit\Model\WithDomainVisitsParams;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\WithDomainVisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\WithDomainVisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Repository\OrphanVisitsCountRepository;
use Shlinkio\Shlink\Core\Visit\Repository\ShortUrlVisitsCountRepository;
use Shlinkio\Shlink\Core\Visit\Repository\VisitRepository;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelper;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use ShlinkioTest\Shlink\Core\Util\ApiKeyDataProviders;

use function array_map;
use function count;
use function range;

class VisitsStatsHelperTest extends TestCase
{
    private VisitsStatsHelper $helper;
    private MockObject & EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->helper = new VisitsStatsHelper($this->em, new UrlShortenerOptions());
    }

    #[Test, DataProvider('provideCounts')]
    public function returnsExpectedVisitsStats(int $expectedCount, ApiKey|null $apiKey): void
    {
        $callCount = 0;
        $visitsCountRepo = $this->createMock(ShortUrlVisitsCountRepository::class);
        $visitsCountRepo->expects($this->exactly(2))->method('countNonOrphanVisits')->willReturnCallback(
            function (VisitsCountFiltering $options) use ($expectedCount, $apiKey, &$callCount) {
                Assert::assertEquals($callCount !== 0, $options->excludeBots);
                Assert::assertEquals($apiKey, $options->apiKey);
                $callCount++;

                return $expectedCount * 3;
            },
        );

        $orphanVisitsCountRepo = $this->createMock(OrphanVisitsCountRepository::class);
        $orphanVisitsCountRepo->expects($this->exactly(2))->method('countOrphanVisits')->with(
            $this->isInstanceOf(VisitsCountFiltering::class),
        )->willReturn($expectedCount);

        $this->em->expects($this->exactly(2))->method('getRepository')->willReturnMap([
            [OrphanVisitsCount::class, $orphanVisitsCountRepo],
            [ShortUrlVisitsCount::class, $visitsCountRepo],
        ]);

        $stats = $this->helper->getVisitsStats($apiKey);

        self::assertEquals(new VisitsStats($expectedCount * 3, $expectedCount), $stats);
    }

    public static function provideCounts(): iterable
    {
        return [
            ...array_map(fn (int $value) => [$value, null], range(0, 50, 5)),
            ...array_map(fn (int $value) => [$value, ApiKey::create()], range(0, 18, 3)),
        ];
    }

    #[Test, DataProviderExternal(ApiKeyDataProviders::class, 'adminApiKeysProvider')]
    public function infoReturnsVisitsForCertainShortCode(ApiKey|null $apiKey): void
    {
        $shortCode = '123ABC';
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode);
        $spec = $apiKey?->spec();

        $repo = $this->createMock(ShortUrlRepository::class);
        $repo->expects($this->once())->method('shortCodeIsInUse')->with($identifier, $spec)->willReturn(true);

        $list = array_map(
            static fn () => Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::empty()),
            range(0, 1),
        );
        $repo2 = $this->createStub(VisitRepository::class);
        $repo2->method('findVisitsByShortCode')->willReturn($list);
        $repo2->method('countVisitsByShortCode')->willReturn(1);

        $this->em->expects($this->exactly(2))->method('getRepository')->willReturnMap([
            [ShortUrl::class, $repo],
            [Visit::class, $repo2],
        ]);

        $paginator = $this->helper->visitsForShortUrl($identifier, new VisitsParams(), $apiKey);

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
    }

    #[Test]
    public function throwsExceptionWhenRequestingVisitsForInvalidShortCode(): void
    {
        $shortCode = '123ABC';
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode);

        $repo = $this->createMock(ShortUrlRepository::class);
        $repo->expects($this->once())->method('shortCodeIsInUse')->with($identifier, null)->willReturn(false);
        $this->em->expects($this->once())->method('getRepository')->with(ShortUrl::class)->willReturn($repo);

        $this->expectException(ShortUrlNotFoundException::class);

        $this->helper->visitsForShortUrl($identifier, new VisitsParams());
    }

    #[Test]
    public function throwsExceptionWhenRequestingVisitsForInvalidTag(): void
    {
        $tag = 'foo';
        $apiKey = ApiKey::create();
        $repo = $this->createMock(TagRepository::class);
        $repo->expects($this->once())->method('tagExists')->with($tag, $apiKey)->willReturn(false);
        $this->em->expects($this->once())->method('getRepository')->with(Tag::class)->willReturn($repo);

        $this->expectException(TagNotFoundException::class);

        $this->helper->visitsForTag($tag, new WithDomainVisitsParams(), $apiKey);
    }

    #[Test, DataProviderExternal(ApiKeyDataProviders::class, 'adminApiKeysProvider')]
    public function visitsForTagAreReturnedAsExpected(ApiKey|null $apiKey): void
    {
        $tag = 'foo';
        $repo = $this->createMock(TagRepository::class);
        $repo->expects($this->once())->method('tagExists')->with($tag, $apiKey)->willReturn(true);

        $list = array_map(
            static fn () => Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::empty()),
            range(0, 1),
        );
        $repo2 = $this->createStub(VisitRepository::class);
        $repo2->method('findVisitsByTag')->willReturn($list);
        $repo2->method('countVisitsByTag')->willReturn(1);

        $this->em->expects($this->exactly(2))->method('getRepository')->willReturnMap([
            [Tag::class, $repo],
            [Visit::class, $repo2],
        ]);

        $paginator = $this->helper->visitsForTag($tag, new WithDomainVisitsParams(), $apiKey);

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
    }

    #[Test]
    public function throwsExceptionWhenRequestingVisitsForInvalidDomain(): void
    {
        $domain = 'foo.com';
        $apiKey = ApiKey::create();
        $repo = $this->createMock(DomainRepository::class);
        $repo->expects($this->once())->method('domainExists')->with($domain, $apiKey)->willReturn(false);
        $this->em->expects($this->once())->method('getRepository')->with(Domain::class)->willReturn($repo);

        $this->expectException(DomainNotFoundException::class);

        $this->helper->visitsForDomain($domain, new VisitsParams(), $apiKey);
    }

    #[Test, DataProviderExternal(ApiKeyDataProviders::class, 'adminApiKeysProvider')]
    public function visitsForNonDefaultDomainAreReturnedAsExpected(ApiKey|null $apiKey): void
    {
        $domain = 'foo.com';
        $repo = $this->createMock(DomainRepository::class);
        $repo->expects($this->once())->method('domainExists')->with($domain, $apiKey)->willReturn(true);

        $list = array_map(
            static fn () => Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::empty()),
            range(0, 1),
        );
        $repo2 = $this->createStub(VisitRepository::class);
        $repo2->method('findVisitsByDomain')->willReturn($list);
        $repo2->method('countVisitsByDomain')->willReturn(1);

        $this->em->expects($this->exactly(2))->method('getRepository')->willReturnMap([
            [Domain::class, $repo],
            [Visit::class, $repo2],
        ]);

        $paginator = $this->helper->visitsForDomain($domain, new VisitsParams(), $apiKey);

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
    }

    #[Test, DataProviderExternal(ApiKeyDataProviders::class, 'adminApiKeysProvider')]
    public function visitsForDefaultDomainAreReturnedAsExpected(ApiKey|null $apiKey): void
    {
        $repo = $this->createMock(DomainRepository::class);
        $repo->expects($this->never())->method('domainExists');

        $list = array_map(
            static fn () => Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::empty()),
            range(0, 1),
        );
        $repo2 = $this->createStub(VisitRepository::class);
        $repo2->method('findVisitsByDomain')->willReturn($list);
        $repo2->method('countVisitsByDomain')->willReturn(1);

        $this->em->expects($this->exactly(2))->method('getRepository')->willReturnMap([
            [Domain::class, $repo],
            [Visit::class, $repo2],
        ]);

        $paginator = $this->helper->visitsForDomain(Domain::DEFAULT_AUTHORITY, new VisitsParams(), $apiKey);

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
    }

    #[Test]
    public function orphanVisitsAreReturnedAsExpected(): void
    {
        $list = array_map(static fn () => Visit::forBasePath(Visitor::empty()), range(0, 3));
        $repo = $this->createMock(VisitRepository::class);
        $repo->expects($this->once())->method('countOrphanVisits')->with(
            $this->isInstanceOf(OrphanVisitsCountFiltering::class),
        )->willReturn(count($list));
        $repo->expects($this->once())->method('findOrphanVisits')->with(
            $this->isInstanceOf(OrphanVisitsListFiltering::class),
        )->willReturn($list);
        $this->em->expects($this->once())->method('getRepository')->with(Visit::class)->willReturn($repo);

        $paginator = $this->helper->orphanVisits(new OrphanVisitsParams());

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
    }

    #[Test]
    public function nonOrphanVisitsAreReturnedAsExpected(): void
    {
        $list = array_map(
            static fn () => Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::empty()),
            range(0, 3),
        );
        $repo = $this->createMock(VisitRepository::class);
        $repo->expects($this->once())->method('countNonOrphanVisits')->with(
            $this->isInstanceOf(WithDOmainVisitsCountFiltering::class),
        )->willReturn(count($list));
        $repo->expects($this->once())->method('findNonOrphanVisits')->with(
            $this->isInstanceOf(WithDOmainVisitsListFiltering::class),
        )->willReturn($list);
        $this->em->expects($this->once())->method('getRepository')->with(Visit::class)->willReturn($repo);

        $paginator = $this->helper->nonOrphanVisits(new WithDomainVisitsParams());

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
    }
}
