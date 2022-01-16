<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit;

use Doctrine\ORM\EntityManagerInterface;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelper;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use ShlinkioTest\Shlink\Core\Util\ApiKeyHelpersTrait;

use function count;
use function Functional\map;
use function range;

class VisitsStatsHelperTest extends TestCase
{
    use ApiKeyHelpersTrait;
    use ProphecyTrait;

    private VisitsStatsHelper $helper;
    private ObjectProphecy $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->helper = new VisitsStatsHelper($this->em->reveal());
    }

    /**
     * @test
     * @dataProvider provideCounts
     */
    public function returnsExpectedVisitsStats(int $expectedCount): void
    {
        $repo = $this->prophesize(VisitRepository::class);
        $count = $repo->countNonOrphanVisits(new VisitsCountFiltering())->willReturn($expectedCount * 3);
        $countOrphan = $repo->countOrphanVisits(Argument::type(VisitsCountFiltering::class))->willReturn(
            $expectedCount,
        );
        $getRepo = $this->em->getRepository(Visit::class)->willReturn($repo->reveal());

        $stats = $this->helper->getVisitsStats();

        self::assertEquals(new VisitsStats($expectedCount * 3, $expectedCount), $stats);
        $count->shouldHaveBeenCalledOnce();
        $countOrphan->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
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

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $count = $repo->shortCodeIsInUse($identifier, $spec)->willReturn(
            true,
        );
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal())->shouldBeCalledOnce();

        $list = map(range(0, 1), fn () => Visit::forValidShortUrl(ShortUrl::createEmpty(), Visitor::emptyInstance()));
        $repo2 = $this->prophesize(VisitRepository::class);
        $repo2->findVisitsByShortCode($identifier, Argument::type(VisitsListFiltering::class))->willReturn($list);
        $repo2->countVisitsByShortCode($identifier, Argument::type(VisitsCountFiltering::class))->willReturn(1);
        $this->em->getRepository(Visit::class)->willReturn($repo2->reveal())->shouldBeCalledOnce();

        $paginator = $this->helper->visitsForShortUrl($identifier, new VisitsParams(), $apiKey);

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
        $count->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function throwsExceptionWhenRequestingVisitsForInvalidShortCode(): void
    {
        $shortCode = '123ABC';
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode);

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $count = $repo->shortCodeIsInUse($identifier, null)->willReturn(
            false,
        );
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal())->shouldBeCalledOnce();

        $this->expectException(ShortUrlNotFoundException::class);
        $count->shouldBeCalledOnce();

        $this->helper->visitsForShortUrl($identifier, new VisitsParams());
    }

    /** @test */
    public function throwsExceptionWhenRequestingVisitsForInvalidTag(): void
    {
        $tag = 'foo';
        $apiKey = ApiKey::create();
        $repo = $this->prophesize(TagRepository::class);
        $tagExists = $repo->tagExists($tag, $apiKey)->willReturn(false);
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());

        $this->expectException(TagNotFoundException::class);
        $tagExists->shouldBeCalledOnce();
        $getRepo->shouldBeCalledOnce();

        $this->helper->visitsForTag($tag, new VisitsParams(), $apiKey);
    }

    /**
     * @test
     * @dataProvider provideAdminApiKeys
     */
    public function visitsForTagAreReturnedAsExpected(?ApiKey $apiKey): void
    {
        $tag = 'foo';
        $repo = $this->prophesize(TagRepository::class);
        $tagExists = $repo->tagExists($tag, $apiKey)->willReturn(true);
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());

        $list = map(range(0, 1), fn () => Visit::forValidShortUrl(ShortUrl::createEmpty(), Visitor::emptyInstance()));
        $repo2 = $this->prophesize(VisitRepository::class);
        $repo2->findVisitsByTag($tag, Argument::type(VisitsListFiltering::class))->willReturn($list);
        $repo2->countVisitsByTag($tag, Argument::type(VisitsCountFiltering::class))->willReturn(1);
        $this->em->getRepository(Visit::class)->willReturn($repo2->reveal())->shouldBeCalledOnce();

        $paginator = $this->helper->visitsForTag($tag, new VisitsParams(), $apiKey);

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
        $tagExists->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function orphanVisitsAreReturnedAsExpected(): void
    {
        $list = map(range(0, 3), fn () => Visit::forBasePath(Visitor::emptyInstance()));
        $repo = $this->prophesize(VisitRepository::class);
        $countVisits = $repo->countOrphanVisits(Argument::type(VisitsCountFiltering::class))->willReturn(count($list));
        $listVisits = $repo->findOrphanVisits(Argument::type(VisitsListFiltering::class))->willReturn($list);
        $getRepo = $this->em->getRepository(Visit::class)->willReturn($repo->reveal());

        $paginator = $this->helper->orphanVisits(new VisitsParams());

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
        $listVisits->shouldHaveBeenCalledOnce();
        $countVisits->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function nonOrphanVisitsAreReturnedAsExpected(): void
    {
        $list = map(range(0, 3), fn () => Visit::forValidShortUrl(ShortUrl::createEmpty(), Visitor::emptyInstance()));
        $repo = $this->prophesize(VisitRepository::class);
        $countVisits = $repo->countNonOrphanVisits(Argument::type(VisitsCountFiltering::class))->willReturn(
            count($list),
        );
        $listVisits = $repo->findNonOrphanVisits(Argument::type(VisitsListFiltering::class))->willReturn($list);
        $getRepo = $this->em->getRepository(Visit::class)->willReturn($repo->reveal());

        $paginator = $this->helper->nonOrphanVisits(new VisitsParams());

        self::assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentPageResults()));
        $listVisits->shouldHaveBeenCalledOnce();
        $countVisits->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
    }
}
