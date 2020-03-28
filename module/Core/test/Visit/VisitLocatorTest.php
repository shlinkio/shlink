<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit;

use Doctrine\ORM\EntityManager;
use Exception;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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

    /** @test */
    public function locateVisitsIteratesAndLocatesUnlocatedVisits(): void
    {
        $unlocatedVisits = map(
            range(1, 200),
            fn (int $i) => new Visit(new ShortUrl(sprintf('short_code_%s', $i)), Visitor::emptyInstance()),
        );

        $findUnlocatedVisits = $this->repo->findUnlocatedVisits()->willReturn($unlocatedVisits);

        $persist = $this->em->persist(Argument::type(Visit::class))->will(function (): void {
        });
        $flush = $this->em->flush()->will(function (): void {
        });
        $clear = $this->em->clear()->will(function (): void {
        });

        $this->visitService->locateUnlocatedVisits(new class implements VisitGeolocationHelperInterface {
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

        $findUnlocatedVisits->shouldHaveBeenCalledOnce();
        $persist->shouldHaveBeenCalledTimes(count($unlocatedVisits));
        $flush->shouldHaveBeenCalledTimes(floor(count($unlocatedVisits) / 200) + 1);
        $clear->shouldHaveBeenCalledTimes(floor(count($unlocatedVisits) / 200) + 1);
    }

    /**
     * @test
     * @dataProvider provideIsNonLocatableAddress
     */
    public function visitsWhichCannotBeLocatedAreIgnoredOrLocatedAsEmpty(bool $isNonLocatableAddress): void
    {
        $unlocatedVisits = [
            new Visit(new ShortUrl('foo'), Visitor::emptyInstance()),
        ];

        $findUnlocatedVisits = $this->repo->findUnlocatedVisits()->willReturn($unlocatedVisits);

        $persist = $this->em->persist(Argument::type(Visit::class))->will(function (): void {
        });
        $flush = $this->em->flush()->will(function (): void {
        });
        $clear = $this->em->clear()->will(function (): void {
        });

        $this->visitService->locateUnlocatedVisits(
            new class ($isNonLocatableAddress) implements VisitGeolocationHelperInterface {
                private bool $isNonLocatableAddress;

                public function __construct(bool $isNonLocatableAddress)
                {
                    $this->isNonLocatableAddress = $isNonLocatableAddress;
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

        $findUnlocatedVisits->shouldHaveBeenCalledOnce();
        $persist->shouldHaveBeenCalledTimes($isNonLocatableAddress ? 1 : 0);
        $flush->shouldHaveBeenCalledOnce();
        $clear->shouldHaveBeenCalledOnce();
    }

    public function provideIsNonLocatableAddress(): iterable
    {
        yield 'The address is locatable' => [false];
        yield 'The address is non-locatable' => [true];
    }
}
