<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Geolocation;

use Doctrine\ORM\EntityManager;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitGeolocationHelperInterface;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitLocator;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Repository\VisitIterationRepositoryInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

use function array_map;
use function count;
use function floor;
use function range;
use function sprintf;

class VisitLocatorTest extends TestCase
{
    private VisitLocator $visitService;
    private MockObject & EntityManager $em;
    private MockObject & VisitIterationRepositoryInterface $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->repo = $this->createMock(VisitIterationRepositoryInterface::class);

        $this->visitService = new VisitLocator($this->em, $this->repo);
    }

    #[Test, DataProvider('provideMethodNames')]
    public function locateVisitsIteratesAndLocatesExpectedVisits(
        string $serviceMethodName,
        string $expectedRepoMethodName,
    ): void {
        $unlocatedVisits = array_map(
            fn (int $i) => Visit::forValidShortUrl(
                ShortUrl::withLongUrl(sprintf('https://short_code_%s', $i)),
                Visitor::empty(),
            ),
            range(1, 200),
        );

        $this->repo->expects($this->once())->method($expectedRepoMethodName)->willReturn($unlocatedVisits);

        $this->em->expects($this->exactly(count($unlocatedVisits)))->method('persist')->with(
            $this->isInstanceOf(Visit::class),
        );
        $this->em->expects($this->exactly((int) floor(count($unlocatedVisits) / 200) + 1))->method('flush');
        $this->em->expects($this->exactly((int) floor(count($unlocatedVisits) / 200) + 1))->method('clear');

        $this->visitService->{$serviceMethodName}(new class implements VisitGeolocationHelperInterface {
            public function geolocateVisit(Visit $visit): Location
            {
                return Location::emptyInstance();
            }

            public function onVisitLocated(VisitLocation $visitLocation, Visit $visit): void
            {
            }
        });
    }

    public static function provideMethodNames(): iterable
    {
        yield 'locateUnlocatedVisits' => ['locateUnlocatedVisits', 'findUnlocatedVisits'];
        yield 'locateVisitsWithEmptyLocation' => ['locateVisitsWithEmptyLocation', 'findVisitsWithEmptyLocation'];
        yield 'locateAllVisits' => ['locateAllVisits', 'findAllVisits'];
    }

    #[Test, DataProvider('provideIsNonLocatableAddress')]
    public function visitsWhichCannotBeLocatedAreIgnoredOrLocatedAsEmpty(
        string $serviceMethodName,
        string $expectedRepoMethodName,
        bool $isNonLocatableAddress,
    ): void {
        $unlocatedVisits = [
            Visit::forValidShortUrl(ShortUrl::withLongUrl('https://foo'), Visitor::empty()),
        ];

        $this->repo->expects($this->once())->method($expectedRepoMethodName)->willReturn($unlocatedVisits);

        $this->em->expects($this->exactly($isNonLocatableAddress ? 1 : 0))->method('persist')->with(
            $this->isInstanceOf(Visit::class),
        );
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('clear');

        $this->visitService->{$serviceMethodName}(
            new class ($isNonLocatableAddress) implements VisitGeolocationHelperInterface {
                public function __construct(private readonly bool $isNonLocatableAddress)
                {
                }

                public function geolocateVisit(Visit $visit): Location
                {
                    throw $this->isNonLocatableAddress
                        ? IpCannotBeLocatedException::forEmptyAddress()
                        : IpCannotBeLocatedException::forError(new Exception(''));
                }

                public function onVisitLocated(VisitLocation $visitLocation, Visit $visit): void
                {
                }
            },
        );
    }

    public static function provideIsNonLocatableAddress(): iterable
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
}
