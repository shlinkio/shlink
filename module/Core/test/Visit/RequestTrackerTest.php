<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\Core\Visit\RequestTracker;
use Shlinkio\Shlink\Core\Visit\VisitsTrackerInterface;

class RequestTrackerTest extends TestCase
{
    use ProphecyTrait;

    private const LONG_URL = 'https://domain.com/foo/bar?some=thing';

    private RequestTracker $requestTracker;
    private ObjectProphecy $visitsTracker;
    private ObjectProphecy $notFoundType;
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->notFoundType = $this->prophesize(NotFoundType::class);
        $this->visitsTracker = $this->prophesize(VisitsTrackerInterface::class);

        $this->requestTracker = new RequestTracker(
            $this->visitsTracker->reveal(),
            new TrackingOptions(['disable_track_param' => 'foobar']),
        );

        $this->request = ServerRequestFactory::fromGlobals()->withAttribute(
            NotFoundType::class,
            $this->notFoundType->reveal(),
        );
    }

    /**
     * @test
     * @dataProvider provideNonTrackingRequests
     */
    public function trackingIsDisabledWhenRequestDoesNotMeetConditions(ServerRequestInterface $request): void
    {
        $shortUrl = ShortUrl::withLongUrl(self::LONG_URL);

        $this->requestTracker->trackIfApplicable($shortUrl, $request);

        $this->visitsTracker->track(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function provideNonTrackingRequests(): iterable
    {
        yield 'forwarded from head' => [ServerRequestFactory::fromGlobals()->withAttribute(
            ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
            RequestMethodInterface::METHOD_HEAD,
        )];
        yield 'disable track param' => [ServerRequestFactory::fromGlobals()->withQueryParams(['foobar' => 'foo'])];
        yield 'disable track param as null' => [
            ServerRequestFactory::fromGlobals()->withQueryParams(['foobar' => null]),
        ];
    }

    /** @test */
    public function trackingHappensOverShortUrlsWhenRequestMeetsConditions(): void
    {
        $shortUrl = ShortUrl::withLongUrl(self::LONG_URL);

        $this->requestTracker->trackIfApplicable($shortUrl, $this->request);

        $this->visitsTracker->track($shortUrl, Argument::type(Visitor::class))->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function baseUrlErrorIsTracked(): void
    {
        $isBaseUrl = $this->notFoundType->isBaseUrl()->willReturn(true);
        $isRegularNotFound = $this->notFoundType->isRegularNotFound()->willReturn(false);
        $isInvalidShortUrl = $this->notFoundType->isInvalidShortUrl()->willReturn(false);

        $this->requestTracker->trackNotFoundIfApplicable($this->request);

        $isBaseUrl->shouldHaveBeenCalledOnce();
        $isRegularNotFound->shouldNotHaveBeenCalled();
        $isInvalidShortUrl->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackBaseUrlVisit(Argument::type(Visitor::class))->shouldHaveBeenCalledOnce();
        $this->visitsTracker->trackRegularNotFoundVisit(Argument::type(Visitor::class))->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackInvalidShortUrlVisit(Argument::type(Visitor::class))->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function regularNotFoundErrorIsTracked(): void
    {
        $isBaseUrl = $this->notFoundType->isBaseUrl()->willReturn(false);
        $isRegularNotFound = $this->notFoundType->isRegularNotFound()->willReturn(true);
        $isInvalidShortUrl = $this->notFoundType->isInvalidShortUrl()->willReturn(false);

        $this->requestTracker->trackNotFoundIfApplicable($this->request);

        $isBaseUrl->shouldHaveBeenCalledOnce();
        $isRegularNotFound->shouldHaveBeenCalledOnce();
        $isInvalidShortUrl->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackBaseUrlVisit(Argument::type(Visitor::class))->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackRegularNotFoundVisit(Argument::type(Visitor::class))->shouldHaveBeenCalledOnce();
        $this->visitsTracker->trackInvalidShortUrlVisit(Argument::type(Visitor::class))->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function invalidShortUrlErrorIsTracked(): void
    {
        $isBaseUrl = $this->notFoundType->isBaseUrl()->willReturn(false);
        $isRegularNotFound = $this->notFoundType->isRegularNotFound()->willReturn(false);
        $isInvalidShortUrl = $this->notFoundType->isInvalidShortUrl()->willReturn(true);

        $this->requestTracker->trackNotFoundIfApplicable($this->request);

        $isBaseUrl->shouldHaveBeenCalledOnce();
        $isRegularNotFound->shouldHaveBeenCalledOnce();
        $isInvalidShortUrl->shouldHaveBeenCalledOnce();
        $this->visitsTracker->trackBaseUrlVisit(Argument::type(Visitor::class))->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackRegularNotFoundVisit(Argument::type(Visitor::class))->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackInvalidShortUrlVisit(Argument::type(Visitor::class))->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideNonTrackingRequests
     */
    public function notFoundIsNotTrackedIfRequestDoesNotMeetConditions(ServerRequestInterface $request): void
    {
        $this->requestTracker->trackNotFoundIfApplicable($request);

        $this->visitsTracker->trackBaseUrlVisit(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackRegularNotFoundVisit(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackInvalidShortUrlVisit(Argument::cetera())->shouldNotHaveBeenCalled();
    }
}
