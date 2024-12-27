<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\RequestTracker;
use Shlinkio\Shlink\Core\Visit\VisitsTrackerInterface;

use const Shlinkio\Shlink\IP_ADDRESS_REQUEST_ATTRIBUTE;

class RequestTrackerTest extends TestCase
{
    private const string LONG_URL = 'https://domain.com/foo/bar?some=thing';

    private RequestTracker $requestTracker;
    private MockObject & VisitsTrackerInterface $visitsTracker;
    private MockObject & NotFoundType $notFoundType;
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->visitsTracker = $this->createMock(VisitsTrackerInterface::class);
        $this->requestTracker = new RequestTracker(
            $this->visitsTracker,
            new TrackingOptions(
                disableTrackParam: 'foobar',
                disableTrackingFrom: ['80.90.100.110', '192.168.10.0/24', '1.2.*.*'],
            ),
        );

        $this->notFoundType = $this->createMock(NotFoundType::class);
        $this->request = ServerRequestFactory::fromGlobals()->withAttribute(
            NotFoundType::class,
            $this->notFoundType,
        );
    }

    #[Test, DataProvider('provideNonTrackingRequests')]
    public function trackingIsDisabledWhenRequestDoesNotMeetConditions(ServerRequestInterface $request): void
    {
        $this->visitsTracker->expects($this->never())->method('track');

        $shortUrl = ShortUrl::withLongUrl(self::LONG_URL);
        $this->requestTracker->trackIfApplicable($shortUrl, $request);
    }

    public static function provideNonTrackingRequests(): iterable
    {
        yield 'forwarded from head' => [ServerRequestFactory::fromGlobals()->withAttribute(
            ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
            RequestMethodInterface::METHOD_HEAD,
        )];
        yield 'disable track param' => [ServerRequestFactory::fromGlobals()->withQueryParams(['foobar' => 'foo'])];
        yield 'disable track param as null' => [
            ServerRequestFactory::fromGlobals()->withQueryParams(['foobar' => null]),
        ];
        yield 'exact remote address' => [ServerRequestFactory::fromGlobals()->withAttribute(
            IP_ADDRESS_REQUEST_ATTRIBUTE,
            '80.90.100.110',
        )];
        yield 'matching wildcard remote address' => [ServerRequestFactory::fromGlobals()->withAttribute(
            IP_ADDRESS_REQUEST_ATTRIBUTE,
            '1.2.3.4',
        )];
        yield 'matching CIDR block remote address' => [ServerRequestFactory::fromGlobals()->withAttribute(
            IP_ADDRESS_REQUEST_ATTRIBUTE,
            '192.168.10.100',
        )];
    }

    #[Test]
    public function trackingHappensOverShortUrlsWhenRequestMeetsConditions(): void
    {
        $shortUrl = ShortUrl::withLongUrl(self::LONG_URL);
        $this->visitsTracker->expects($this->once())->method('track')->with(
            $shortUrl,
            $this->isInstanceOf(Visitor::class),
        );

        $this->requestTracker->trackIfApplicable($shortUrl, $this->request);
    }

    #[Test]
    public function trackingHappensOverShortUrlsWhenRemoteAddressIsInvalid(): void
    {
        $shortUrl = ShortUrl::withLongUrl(self::LONG_URL);
        $this->visitsTracker->expects($this->once())->method('track')->with(
            $shortUrl,
            $this->isInstanceOf(Visitor::class),
        );

        $this->requestTracker->trackIfApplicable($shortUrl, ServerRequestFactory::fromGlobals()->withAttribute(
            IP_ADDRESS_REQUEST_ATTRIBUTE,
            'invalid',
        ));
    }

    #[Test]
    public function baseUrlErrorIsTracked(): void
    {
        $this->notFoundType->expects($this->once())->method('isBaseUrl')->willReturn(true);
        $this->notFoundType->expects($this->never())->method('isRegularNotFound');
        $this->notFoundType->expects($this->never())->method('isInvalidShortUrl');
        $this->visitsTracker->expects($this->once())->method('trackBaseUrlVisit')->with(
            $this->isInstanceOf(Visitor::class),
        );
        $this->visitsTracker->expects($this->never())->method('trackRegularNotFoundVisit');
        $this->visitsTracker->expects($this->never())->method('trackInvalidShortUrlVisit');

        $this->requestTracker->trackNotFoundIfApplicable($this->request);
    }

    #[Test]
    public function regularNotFoundErrorIsTracked(): void
    {
        $this->notFoundType->expects($this->once())->method('isBaseUrl')->willReturn(false);
        $this->notFoundType->expects($this->once())->method('isRegularNotFound')->willReturn(true);
        $this->notFoundType->expects($this->never())->method('isInvalidShortUrl');
        $this->visitsTracker->expects($this->never())->method('trackBaseUrlVisit');
        $this->visitsTracker->expects($this->once())->method('trackRegularNotFoundVisit')->with(
            $this->isInstanceOf(Visitor::class),
        );
        $this->visitsTracker->expects($this->never())->method('trackInvalidShortUrlVisit');

        $this->requestTracker->trackNotFoundIfApplicable($this->request);
    }

    #[Test]
    public function invalidShortUrlErrorIsTracked(): void
    {
        $this->notFoundType->expects($this->once())->method('isBaseUrl')->willReturn(false);
        $this->notFoundType->expects($this->once())->method('isRegularNotFound')->willReturn(false);
        $this->notFoundType->expects($this->once())->method('isInvalidShortUrl')->willReturn(true);
        $this->visitsTracker->expects($this->never())->method('trackBaseUrlVisit');
        $this->visitsTracker->expects($this->never())->method('trackRegularNotFoundVisit');
        $this->visitsTracker->expects($this->once())->method('trackInvalidShortUrlVisit')->with(
            $this->isInstanceOf(Visitor::class),
        );

        $this->requestTracker->trackNotFoundIfApplicable($this->request);
    }

    #[Test, DataProvider('provideNonTrackingRequests')]
    public function notFoundIsNotTrackedIfRequestDoesNotMeetConditions(ServerRequestInterface $request): void
    {
        $this->visitsTracker->expects($this->never())->method('trackBaseUrlVisit');
        $this->visitsTracker->expects($this->never())->method('trackRegularNotFoundVisit');
        $this->visitsTracker->expects($this->never())->method('trackInvalidShortUrlVisit');

        $this->requestTracker->trackNotFoundIfApplicable($request);
    }
}
