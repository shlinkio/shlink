<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit;

use Doctrine\ORM\EntityManagerInterface;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepository;
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Repository\TagRepository;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Repository\VisitRepository;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelper;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use ShlinkioTest\Shlink\Core\Util\ApiKeyHelpersTrait;

use function count;
use function Functional\map;
use function range;

class VisitsStatsHelperTest extends TestCase
{
    use ApiKeyHelpersTrait;

    private VisitsStatsHelper $helper;
    private MockObject $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->helper = new VisitsStatsHelper($this->em);
    }

    /**
     * @test
     * @dataProvider provideCounts
     */
    public function returnsExpectedVisitsStats(int $expectedCount): void
    {
        $repo = $this->createMock(VisitRepository::class);
        $repo->expects($this->once())->method('countNonOrphanVisits')->with(new VisitsCountFiltering())->willReturn(
            $expectedCount * 3,
        );
        $repo->expects($this->once())->method('countOrphanVisits')->with(
            $this->isInstanceOf(VisitsCountFiltering::class),
        )->willReturn($expectedCount);
        $this->em->expects($this->once())->method('getRepository')->with(Visit::class)->willReturn($repo);

        $stats = $this->helper->getVisitsStats();

        self::assertEquals(new VisitsStats($expectedCount * 3, $expectedCount), $stats);
    }

    public function provideCounts(): iterable
    {
        return map(range(0, 50, 5), fn (int $value) => [$value]);
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function infoReturnsVisitsForCertainShortCode(?ApiKey $apiKey): void
    {
        $shortCode = '123ABC';
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode);
        $spec = $apiKey?->spec();

        $repo = $this->createMock(ShortUrlRepositoryInterface::class);
        $repo->expects($this->once())->method('shortCodeIsInUse')->with($identifier, $spec)->willReturn(true);

        $list = map(range(0, 1), fn () => Visit::forValidShortUrl(ShortUrl::createEmpty(), Visitor::emptyInstance()));
        $repo2 = $this->createMock(VisitRepository::class);
        $repo2->method('findVisitsByShortCode')->with(
            $identifier,
            $this->isInstanceOf(VisitsListFiltering::class),
        )->willReturn($list);
        $repo2->method('countVisitsByShortCode')->with(
            $identifier,
            $this->isInstanceOf(VisitsCountFiltering::class),
        )->willReturn(1);

        $this->em->expects($this->exactly(2))->method('getRepository')->willReturnMap([
            [ShortUrl::class, $repo],
            [Visit::class, $repo2],
        ]);

        $paginator = $this->helper->visitsForShortUrl($identifier, new VisitsParams(), $apiKey);

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
    }

    /** @test */
    public function throwsExceptionWhenRequestingVisitsForInvalidShortCode(): void
    {
        $shortCode = '123ABC';
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode);

        $repo = $this->createMock(ShortUrlRepositoryInterface::class);
        $repo->expects($this->once())->method('shortCodeIsInUse')->with($identifier, null)->willReturn(false);
        $this->em->expects($this->once())->method('getRepository')->with(ShortUrl::class)->willReturn($repo);

        $this->expectException(ShortUrlNotFoundException::class);

        $this->helper->visitsForShortUrl($identifier, new VisitsParams());
    }

    /** @test */
    public function throwsExceptionWhenRequestingVisitsForInvalidTag(): void
    {
        $tag = 'foo';
        $apiKey = ApiKey::create();
        $repo = $this->createMock(TagRepository::class);
        $repo->expects($this->once())->method('tagExists')->with($tag, $apiKey)->willReturn(false);
        $this->em->expects($this->once())->method('getRepository')->with(Tag::class)->willReturn($repo);

        $this->expectException(TagNotFoundException::class);

        $this->helper->visitsForTag($tag, new VisitsParams(), $apiKey);
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function visitsForTagAreReturnedAsExpected(?ApiKey $apiKey): void
    {
        $tag = 'foo';
        $repo = $this->createMock(TagRepository::class);
        $repo->expects($this->once())->method('tagExists')->with($tag, $apiKey)->willReturn(true);

        $list = map(range(0, 1), fn () => Visit::forValidShortUrl(ShortUrl::createEmpty(), Visitor::emptyInstance()));
        $repo2 = $this->createMock(VisitRepository::class);
        $repo2->method('findVisitsByTag')->with($tag, $this->isInstanceOf(VisitsListFiltering::class))->willReturn(
            $list,
        );
        $repo2->method('countVisitsByTag')->with($tag, $this->isInstanceOf(VisitsCountFiltering::class))->willReturn(1);

        $this->em->expects($this->exactly(2))->method('getRepository')->willReturnMap([
            [Tag::class, $repo],
            [Visit::class, $repo2],
        ]);

        $paginator = $this->helper->visitsForTag($tag, new VisitsParams(), $apiKey);

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
    }

    /** @test */
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

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function visitsForNonDefaultDomainAreReturnedAsExpected(?ApiKey $apiKey): void
    {
        $domain = 'foo.com';
        $repo = $this->createMock(DomainRepository::class);
        $repo->expects($this->once())->method('domainExists')->with($domain, $apiKey)->willReturn(true);

        $list = map(range(0, 1), fn () => Visit::forValidShortUrl(ShortUrl::createEmpty(), Visitor::emptyInstance()));
        $repo2 = $this->createMock(VisitRepository::class);
        $repo2->method('findVisitsByDomain')->with(
            $domain,
            $this->isInstanceOf(VisitsListFiltering::class),
        )->willReturn($list);
        $repo2->method('countVisitsByDomain')->with(
            $domain,
            $this->isInstanceOf(VisitsCountFiltering::class),
        )->willReturn(1);

        $this->em->expects($this->exactly(2))->method('getRepository')->willReturnMap([
            [Domain::class, $repo],
            [Visit::class, $repo2],
        ]);

        $paginator = $this->helper->visitsForDomain($domain, new VisitsParams(), $apiKey);

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function visitsForDefaultDomainAreReturnedAsExpected(?ApiKey $apiKey): void
    {
        $repo = $this->createMock(DomainRepository::class);
        $repo->expects($this->never())->method('domainExists');

        $list = map(range(0, 1), fn () => Visit::forValidShortUrl(ShortUrl::createEmpty(), Visitor::emptyInstance()));
        $repo2 = $this->createMock(VisitRepository::class);
        $repo2->method('findVisitsByDomain')->with(
            'DEFAULT',
            $this->isInstanceOf(VisitsListFiltering::class),
        )->willReturn($list);
        $repo2->method('countVisitsByDomain')->with(
            'DEFAULT',
            $this->isInstanceOf(VisitsCountFiltering::class),
        )->willReturn(1);

        $this->em->expects($this->exactly(2))->method('getRepository')->willReturnMap([
            [Domain::class, $repo],
            [Visit::class, $repo2],
        ]);

        $paginator = $this->helper->visitsForDomain('DEFAULT', new VisitsParams(), $apiKey);

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
    }

    /** @test */
    public function orphanVisitsAreReturnedAsExpected(): void
    {
        $list = map(range(0, 3), fn () => Visit::forBasePath(Visitor::emptyInstance()));
        $repo = $this->createMock(VisitRepository::class);
        $repo->expects($this->once())->method('countOrphanVisits')->with(
            $this->isInstanceOf(VisitsCountFiltering::class),
        )->willReturn(count($list));
        $repo->expects($this->once())->method('findOrphanVisits')->with(
            $this->isInstanceOf(VisitsListFiltering::class),
        )->willReturn($list);
        $this->em->expects($this->once())->method('getRepository')->with(Visit::class)->willReturn($repo);

        $paginator = $this->helper->orphanVisits(new VisitsParams());

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
    }

    /** @test */
    public function nonOrphanVisitsAreReturnedAsExpected(): void
    {
        $list = map(range(0, 3), fn () => Visit::forValidShortUrl(ShortUrl::createEmpty(), Visitor::emptyInstance()));
        $repo = $this->createMock(VisitRepository::class);
        $repo->expects($this->once())->method('countNonOrphanVisits')->with(
            $this->isInstanceOf(VisitsCountFiltering::class),
        )->willReturn(count($list));
        $repo->expects($this->once())->method('findNonOrphanVisits')->with(
            $this->isInstanceOf(VisitsListFiltering::class),
        )->willReturn($list);
        $this->em->expects($this->once())->method('getRepository')->with(Visit::class)->willReturn($repo);

        $paginator = $this->helper->nonOrphanVisits(new VisitsParams());

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
    }
}
