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
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;
use Shlinkio\Shlink\Core\Geolocation\Middleware\IpGeolocationMiddleware;
use Shlinkio\Shlink\Geolocation\Exception\LocationResolutionException;
use Shlinkio\Shlink\Geolocation\GeoLite2\DbUpdaterInterface;
use Shlinkio\Shlink\Geolocation\Model\Location;
use Shlinkio\Shlink\Geolocation\Resolver\LocationResolverInterface;
use Throwable;

use const Shlinkio\Shlink\Geolocation\IP_ADDRESS_REQUEST_ATTRIBUTE;

class IpGeolocationMiddlewareTest extends TestCase
{
    private MockObject&LocationResolverInterface $ipLocationResolver;
    private MockObject&DbUpdaterInterface $dbUpdater;
    private MockObject&LoggerInterface $logger;
    private MockObject&RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->ipLocationResolver = $this->createMock(LocationResolverInterface::class);
        $this->dbUpdater = $this->createMock(DbUpdaterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    #[Test]
    public function geolocationIsSkippedIfTrackingIsDisabled(): void
    {
        $this->dbUpdater->expects($this->never())->method('databaseFileExists');
        $this->ipLocationResolver->expects($this->never())->method('resolveRequestLocation');
        $this->logger->expects($this->never())->method('warning');

        $request = ServerRequestFactory::fromGlobals();
        $this->handler->expects($this->once())->method('handle')->with($request);

        $this->middleware(disableTracking: true)->process($request, $this->handler);
    }

    #[Test]
    public function warningIsLoggedIfGeoLiteDbDoesNotExist(): void
    {
        $this->ipLocationResolver->expects($this->never())->method('resolveRequestLocation');
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(false);
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
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
        $this->ipLocationResolver->expects($this->never())->method('resolveRequestLocation');
        $this->logger->expects($this->never())->method('warning');

        $request = ServerRequestFactory::fromGlobals()->withAttribute(IP_ADDRESS_REQUEST_ATTRIBUTE, $ipAddress);
        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                static function (ServerRequestInterface $req): bool {
                    $location = $req->getAttribute(Location::class);
                    if (!$location instanceof Location) {
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
        $request = ServerRequestFactory::fromGlobals()->withAttribute(IP_ADDRESS_REQUEST_ATTRIBUTE, '1.2.3.4');

        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(true);
        $this->ipLocationResolver
            ->expects($this->once())
            ->method('resolveRequestLocation')
            ->with($request)
            ->willReturn(new Location(countryCode: 'ES'));
        $this->logger->expects($this->never())->method('warning');

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                static function (ServerRequestInterface $req): bool {
                    $location = $req->getAttribute(Location::class);
                    if (!$location instanceof Location) {
                        return false;
                    }

                    Assert::assertEquals('ES', $location->countryCode);
                    return true;
                },
            ));

        $this->middleware()->process($request, $this->handler);
    }

    /**
     * @param non-empty-string $loggerMethod
     */
    #[Test]
    #[TestWith([
        new LocationResolutionException(),
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
        $request = ServerRequestFactory::fromGlobals()->withAttribute(IP_ADDRESS_REQUEST_ATTRIBUTE, '1.2.3.4');

        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(true);
        $this->ipLocationResolver
            ->expects($this->once())
            ->method('resolveRequestLocation')
            ->with($request)
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method($loggerMethod)->with($expectedLoggedMessage, ['e' => $exception]);

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                static function (ServerRequestInterface $req): bool {
                    $location = $req->getAttribute(Location::class);
                    if (!$location instanceof Location) {
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
