<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\ServerRequest;
use Laminas\Paginator\Adapter\ArrayAdapter;
use Laminas\Paginator\Paginator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
use Shlinkio\Shlink\Rest\Action\Visit\ShortUrlVisitsAction;

class ShortUrlVisitsActionTest extends TestCase
{
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
        $this->visitsTracker->info(new ShortUrlIdentifier($shortCode), Argument::type(VisitsParams::class))->willReturn(
            new Paginator(new ArrayAdapter([])),
        )->shouldBeCalledOnce();

        $response = $this->action->handle((new ServerRequest())->withAttribute('shortCode', $shortCode));
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function paramsAreReadFromQuery(): void
    {
        $shortCode = 'abc123';
        $this->visitsTracker->info(new ShortUrlIdentifier($shortCode), new VisitsParams(
            new DateRange(null, Chronos::parse('2016-01-01 00:00:00')),
            3,
            10,
        ))
            ->willReturn(new Paginator(new ArrayAdapter([])))
            ->shouldBeCalledOnce();

        $response = $this->action->handle(
            (new ServerRequest())->withAttribute('shortCode', $shortCode)
                                 ->withQueryParams([
                                     'endDate' => '2016-01-01 00:00:00',
                                     'page' => '3',
                                     'itemsPerPage' => '10',
                                 ]),
        );
        $this->assertEquals(200, $response->getStatusCode());
    }
}
