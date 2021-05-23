<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit;

use Doctrine\ORM\EntityManager;
use Exception;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Core\Visit\VisitGeolocationHelperInterface;
use Shlinkio\Shlink\Core\Visit\VisitLocator;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

use function array_shift;
use function count;
use function floor;
use function func_get_args;
use function Functional\map;
use function range;
use function sprintf;

class VisitLocatorTest extends TestCase
{
    use ProphecyTrait;

    private VisitLocator $visitService;
    private ObjectProphecy $em;
    private ObjectProphecy $repo;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManager::class);
        $this->repo = $this->prophesize(VisitRepositoryInterface::class);
        $this->em->getRepository(Visit::class)->willReturn($this->repo->reveal());

        $this->visitService = new VisitLocator($this->em->reveal());
    }

    /**
     * @test
     * @dataProvider provideMethodNames
     */
    public function locateVisitsIteratesAndLocatesExpectedVisits(
        string $serviceMethodName,
        string $expectedRepoMethodName,
    ): void {
        $unlocatedVisits = map(
            range(1, 200),
            fn (int $i) =>
                Visit::forValidShortUrl(ShortUrl::withLongUrl(sprintf('short_code_%s', $i)), Visitor::emptyInstance()),
        );

        $findVisits = $this->mockRepoMethod($expectedRepoMethodName)->willReturn($unlocatedVisits);

        $persist = $this->em->persist(Argument::type(Visit::class))->will(function (): void {
        });
        $flush = $this->em->flush()->will(function (): void {
        });
        $clear = $this->em->clear()->will(function (): void {
        });

        $this->visitService->{$serviceMethodName}(new class implements VisitGeolocationHelperInterface {
            public function geolocateVisit(Visit $visit): Location
            {
                return Location::emptyInstance();
            }

            public function onVisitLocated(VisitLocation $visitLocation, Visit $visit): void
            {
                $args = func_get_args();

                Assert::assertInstanceOf(VisitLocation::class, array_shift($args));
                Assert::assertInstanceOf(Visit::class, array_shift($args));
            }
        });

        $findVisits->shouldHaveBeenCalledOnce();
        $persist->shouldHaveBeenCalledTimes(count($unlocatedVisits));
        $flush->shouldHaveBeenCalledTimes(floor(count($unlocatedVisits) / 200) + 1);
        $clear->shouldHaveBeenCalledTimes(floor(count($unlocatedVisits) / 200) + 1);
    }

    public function provideMethodNames(): iterable
    {
        yield 'locateUnlocatedVisits' => ['locateUnlocatedVisits', 'findUnlocatedVisits'];
        yield 'locateVisitsWithEmptyLocation' => ['locateVisitsWithEmptyLocation', 'findVisitsWithEmptyLocation'];
        yield 'locateAllVisits' => ['locateAllVisits', 'findAllVisits'];
    }

    /**
     * @test
     * @dataProvider provideIsNonLocatableAddress
     */
    public function visitsWhichCannotBeLocatedAreIgnoredOrLocatedAsEmpty(
        string $serviceMethodName,
        string $expectedRepoMethodName,
        bool $isNonLocatableAddress,
    ): void {
        $unlocatedVisits = [
            Visit::forValidShortUrl(ShortUrl::withLongUrl('foo'), Visitor::emptyInstance()),
        ];

        $findVisits = $this->mockRepoMethod($expectedRepoMethodName)->willReturn($unlocatedVisits);

        $persist = $this->em->persist(Argument::type(Visit::class))->will(function (): void {
        });
        $flush = $this->em->flush()->will(function (): void {
        });
        $clear = $this->em->clear()->will(function (): void {
        });

        $this->visitService->{$serviceMethodName}(
            new class ($isNonLocatableAddress) implements VisitGeolocationHelperInterface {
                public function __construct(private bool $isNonLocatableAddress)
                {
                }

                public function geolocateVisit(Visit $visit): Location
                {
                    throw $this->isNonLocatableAddress
                        ? new IpCannotBeLocatedException('Cannot be located')
                        : IpCannotBeLocatedException::forError(new Exception(''));
                }

                public function onVisitLocated(VisitLocation $visitLocation, Visit $visit): void
                {
                }
            },
        );

        $findVisits->shouldHaveBeenCalledOnce();
        $persist->shouldHaveBeenCalledTimes($isNonLocatableAddress ? 1 : 0);
        $flush->shouldHaveBeenCalledOnce();
        $clear->shouldHaveBeenCalledOnce();
    }

    public function provideIsNonLocatableAddress(): iterable
    {
        yield 'locateUnlocatedVisits - locatable address' => ['locateUnlocatedVisits', 'findUnlocatedVisits', false];
        yield 'locateUnlocatedVisits - non-locatable address' => ['locateUnlocatedVisits', 'findUnlocatedVisits', true];
        yield 'locateVisitsWithEmptyLocation - locatable address' => [
            'locateVisitsWithEmptyLocation',
            'findVisitsWithEmptyLocation',
            false,
        ];
        yield 'locateVisitsWithEmptyLocation - non-locatable address' => [
            'locateVisitsWithEmptyLocation',
            'findVisitsWithEmptyLocation',
            true,
        ];
        yield 'locateAllVisits - locatable address' => ['locateAllVisits', 'findAllVisits', false];
        yield 'locateAllVisits - non-locatable address' => ['locateAllVisits', 'findAllVisits', true];
    }

    private function mockRepoMethod(string $methodName): MethodProphecy
    {
        return (new MethodProphecy($this->repo, $methodName, new Argument\ArgumentsWildcard([])));
    }
}
