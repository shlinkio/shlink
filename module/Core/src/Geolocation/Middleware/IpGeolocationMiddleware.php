<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Geolocation\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;
use Shlinkio\Shlink\Geolocation\Exception\LocationResolutionException;
use Shlinkio\Shlink\Geolocation\GeoLite2\DbUpdaterInterface;
use Shlinkio\Shlink\Geolocation\Model\Location;
use Shlinkio\Shlink\Geolocation\Resolver\LocationResolverInterface;
use Throwable;

readonly class IpGeolocationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LocationResolverInterface $ipLocationResolver,
        private DbUpdaterInterface $dbUpdater,
        private LoggerInterface $logger,
        private TrackingOptions $trackingOptions,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->trackingOptions->isGeolocationRelevant()) {
            return $handler->handle($request);
        }

        if (!$this->dbUpdater->databaseFileExists()) {
            $this->logger->warning('Tried to geolocate IP address, but a GeoLite2 db was not found.');
            return $handler->handle($request);
        }

        $location = $this->geolocateIpAddress($request);
        return $handler->handle($request->withAttribute(Location::class, $location));
    }

    private function geolocateIpAddress(ServerRequestInterface $request): Location
    {
        try {
            return $this->ipLocationResolver->resolveRequestLocation($request);
        } catch (LocationResolutionException $e) {
            $this->logger->warning('Tried to geolocate visitor, but it was not possible. {e}', ['e' => $e]);
            return Location::empty();
        } catch (Throwable $e) {
            $this->logger->error('An unexpected error occurred while trying to locate IP address. {e}', ['e' => $e]);
            return Location::empty();
        }
    }
}
