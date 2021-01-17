<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Paginator\Adapter\ArrayAdapter;
use Laminas\Paginator\Paginator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
use Shlinkio\Shlink\Rest\Action\Visit\ShortUrlVisitsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlVisitsActionTest extends TestCase
{
    use ProphecyTrait;

    private ShortUrlVisitsAction $action;
    private ObjectProphecy $visitsTracker;

    public function setUp(): void
    {
        $this->visitsTracker = $this->prophesize(VisitsTracker::class);
        $this->action = new ShortUrlVisitsAction($this->visitsTracker->reveal());
    }

    /** @test */
    public function providingCorrectShortCodeReturnsVisits(): void
    {
        $shortCode = 'abc123';
        $this->visitsTracker->info(
            new ShortUrlIdentifier($shortCode),
            Argument::type(VisitsParams::class),
            Argument::type(ApiKey::class),
        )->willReturn(new Paginator(new ArrayAdapter([])))
         ->shouldBeCalledOnce();

        $response = $this->action->handle($this->requestWithApiKey()->withAttribute('shortCode', $shortCode));
        self::assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function paramsAreReadFromQuery(): void
    {
        $shortCode = 'abc123';
        $this->visitsTracker->info(new ShortUrlIdentifier($shortCode), new VisitsParams(
            new DateRange(null, Chronos::parse('2016-01-01 00:00:00')),
            3,
            10,
        ), Argument::type(ApiKey::class))
            ->willReturn(new Paginator(new ArrayAdapter([])))
            ->shouldBeCalledOnce();

        $response = $this->action->handle(
            $this->requestWithApiKey()->withAttribute('shortCode', $shortCode)
                                      ->withQueryParams([
                                          'endDate' => '2016-01-01 00:00:00',
                                          'page' => '3',
                                          'itemsPerPage' => '10',
                                      ]),
        );
        self::assertEquals(200, $response->getStatusCode());
    }

    private function requestWithApiKey(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, new ApiKey());
    }
}
