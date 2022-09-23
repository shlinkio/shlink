<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Geolocation;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitToLocationHelper;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;

class VisitToLocationHelperTest extends TestCase
{
    use ProphecyTrait;

    private VisitToLocationHelper $helper;
    private ObjectProphecy $ipLocationResolver;

    protected function setUp(): void
    {
        $this->ipLocationResolver = $this->prophesize(IpLocationResolverInterface::class);
        $this->helper = new VisitToLocationHelper($this->ipLocationResolver->reveal());
    }

    /**
     * @test
     * @dataProvider provideNonLocatableVisits
     */
    public function throwsExpectedErrorForNonLocatableVisit(
        Visit $visit,
        IpCannotBeLocatedException $expectedException,
    ): void {
        $this->expectExceptionObject($expectedException);
        $this->ipLocationResolver->resolveIpLocation(Argument::cetera())->shouldNotBeCalled();

        $this->helper->resolveVisitLocation($visit);
    }

    public function provideNonLocatableVisits(): iterable
    {
        yield [Visit::forBasePath(Visitor::emptyInstance()), IpCannotBeLocatedException::forEmptyAddress()];
        yield [
            Visit::forBasePath(new Visitor('foo', 'bar', IpAddress::LOCALHOST, '')),
            IpCannotBeLocatedException::forLocalhost(),
        ];
    }

    /** @test */
    public function throwsGenericErrorWhenResolvingIpFails(): void
    {
        $e = new WrongIpException('');

        $this->expectExceptionObject(IpCannotBeLocatedException::forError($e));
        $this->ipLocationResolver->resolveIpLocation(Argument::cetera())->willThrow($e)
                                                                        ->shouldBeCalledOnce();

        $this->helper->resolveVisitLocation(Visit::forBasePath(new Visitor('foo', 'bar', '1.2.3.4', '')));
    }
}
