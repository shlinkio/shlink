<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Geolocation\Middleware;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shlinkio\Shlink\Common\Middleware\IpAddressMiddlewareFactory;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;
use Shlinkio\Shlink\Core\Geolocation\Middleware\IpGeolocationMiddleware;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;
use Throwable;

class IpGeolocationMiddlewareTest extends TestCase
{
    private MockObject & IpLocationResolverInterface $ipLocationResolver;
    private MockObject & DbUpdaterInterface $dbUpdater;
    private MockObject & LoggerInterface $logger;
    private MockObject & RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->ipLocationResolver = $this->createMock(IpLocationResolverInterface::class);
        $this->dbUpdater = $this->createMock(DbUpdaterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    #[Test]
    public function geolocationIsSkippedIfTrackingIsDisabled(): void
    {
        $this->dbUpdater->expects($this->never())->method('databaseFileExists');
        $this->ipLocationResolver->expects($this->never())->method('resolveIpLocation');
        $this->logger->expects($this->never())->method('warning');

        $request = ServerRequestFactory::fromGlobals();
        $this->handler->expects($this->once())->method('handle')->with($request);

        $this->middleware(disableTracking: true)->process($request, $this->handler);
    }

    #[Test]
    public function warningIsLoggedIfGeoLiteDbDoesNotExist(): void
    {
        $this->ipLocationResolver->expects($this->never())->method('resolveIpLocation');
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(false);
        $this->logger->expects($this->once())->method('warning')->with(
            'Tried to geolocate IP address, but a GeoLite2 db was not found.',
        );

        $request = ServerRequestFactory::fromGlobals();
        $this->handler->expects($this->once())->method('handle')->with($request);

        $this->middleware()->process($request, $this->handler);
    }

    #[Test]
    #[TestWith([null])]
    #[TestWith([IpAddress::LOCALHOST])]
    public function emptyLocationIsReturnedIfIpAddressIsNotLocatable(string|null $ipAddress): void
    {
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(true);
        $this->ipLocationResolver->expects($this->never())->method('resolveIpLocation');
        $this->logger->expects($this->never())->method('warning');

        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            IpAddressMiddlewareFactory::REQUEST_ATTR,
            $ipAddress,
        );
        $this->handler->expects($this->once())->method('handle')->with($this->callback(
            function (ServerRequestInterface $req): bool {
                $location = $req->getAttribute(Location::class);
                if (! $location instanceof Location) {
                    return false;
                }

                Assert::assertEmpty($location->countryCode);
                return true;
            },
        ));

        $this->middleware()->process($request, $this->handler);
    }

    #[Test]
    public function locationIsResolvedFromIpAddress(): void
    {
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(true);
        $this->ipLocationResolver->expects($this->once())->method('resolveIpLocation')->with('1.2.3.4')->willReturn(
            new Location(countryCode: 'ES'),
        );
        $this->logger->expects($this->never())->method('warning');

        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            IpAddressMiddlewareFactory::REQUEST_ATTR,
            '1.2.3.4',
        );
        $this->handler->expects($this->once())->method('handle')->with($this->callback(
            function (ServerRequestInterface $req): bool {
                $location = $req->getAttribute(Location::class);
                if (! $location instanceof Location) {
                    return false;
                }

                Assert::assertEquals('ES', $location->countryCode);
                return true;
            },
        ));

        $this->middleware()->process($request, $this->handler);
    }

    #[Test]
    #[TestWith([
        new WrongIpException(),
        'warning',
        'Tried to locate IP address, but it seems to be wrong. {e}',
    ])]
    #[TestWith([
        new RuntimeException('Unknown'),
        'error',
        'An unexpected error occurred while trying to locate IP address. {e}',
    ])]
    public function warningIsPrintedIfAnErrorOccurs(
        Throwable $exception,
        string $loggerMethod,
        string $expectedLoggedMessage,
    ): void {
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(true);
        $this->ipLocationResolver
            ->expects($this->once())
            ->method('resolveIpLocation')
            ->with('1.2.3.4')
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method($loggerMethod)->with($expectedLoggedMessage, ['e' => $exception]);

        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            IpAddressMiddlewareFactory::REQUEST_ATTR,
            '1.2.3.4',
        );
        $this->handler->expects($this->once())->method('handle')->with($this->callback(
            function (ServerRequestInterface $req): bool {
                $location = $req->getAttribute(Location::class);
                if (! $location instanceof Location) {
                    return false;
                }

                Assert::assertEmpty($location->countryCode);
                return true;
            },
        ));

        $this->middleware()->process($request, $this->handler);
    }

    private function middleware(bool $disableTracking = false): IpGeolocationMiddleware
    {
        return new IpGeolocationMiddleware(
            $this->ipLocationResolver,
            $this->dbUpdater,
            $this->logger,
            new TrackingOptions(disableTracking: $disableTracking),
        );
    }
}
