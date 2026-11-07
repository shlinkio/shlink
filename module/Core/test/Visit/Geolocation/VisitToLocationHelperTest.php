<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Geolocation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitToLocationHelper;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Geolocation\Exception\LocationResolutionException;
use Shlinkio\Shlink\Geolocation\Resolver\LocationResolverInterface;

class VisitToLocationHelperTest extends TestCase
{
    private VisitToLocationHelper $helper;
    private MockObject&LocationResolverInterface $ipLocationResolver;

    protected function setUp(): void
    {
        $this->ipLocationResolver = $this->createMock(LocationResolverInterface::class);
        $this->helper = new VisitToLocationHelper($this->ipLocationResolver);
    }

    #[Test, DataProvider('provideNonLocatableVisits')]
    public function throwsExpectedErrorForNonLocatableVisit(
        Visit $visit,
        IpCannotBeLocatedException $expectedException,
    ): void {
        $this->expectExceptionObject($expectedException);
        $this->ipLocationResolver->expects($this->never())->method('resolveRequestLocation');

        $this->helper->resolveVisitLocation($visit);
    }

    public static function provideNonLocatableVisits(): iterable
    {
        yield [Visit::forBasePath(Visitor::empty()), IpCannotBeLocatedException::forEmptyAddress()];
        yield [
            Visit::forBasePath(Visitor::fromParams('foo', 'bar', IpAddress::LOCALHOST)),
            IpCannotBeLocatedException::forLocalhost(),
        ];
    }

    #[Test]
    public function throwsGenericErrorWhenResolvingIpFails(): void
    {
        $e = LocationResolutionException::anonymous();

        $this->expectExceptionObject(IpCannotBeLocatedException::forError($e));
        $this->ipLocationResolver->expects($this->once())->method('resolveRequestLocation')->willThrowException($e);

        $this->helper->resolveVisitLocation(Visit::forBasePath(Visitor::fromParams('foo', 'bar', '1.2.3.4')));
    }
}
