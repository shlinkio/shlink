<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
use Shlinkio\Shlink\Rest\Action\Visit\GetVisitsAction;
use Zend\Diactoros\ServerRequestFactory;
use Zend\I18n\Translator\Translator;

class GetVisitsActionTest extends TestCase
{
    /**
     * @var GetVisitsAction
     */
    protected $action;
    /**
     * @var ObjectProphecy
     */
    protected $visitsTracker;

    public function setUp()
    {
        $this->visitsTracker = $this->prophesize(VisitsTracker::class);
        $this->action = new GetVisitsAction($this->visitsTracker->reveal(), Translator::factory([]));
    }

    /**
     * @test
     */
    public function providingCorrectShortCodeReturnsVisits()
    {
        $shortCode = 'abc123';
        $this->visitsTracker->info($shortCode, Argument::type(DateRange::class))->willReturn([])
                                                                                ->shouldBeCalledTimes(1);

        $response = $this->action->handle(ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode));
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function providingInvalidShortCodeReturnsError()
    {
        $shortCode = 'abc123';
        $this->visitsTracker->info($shortCode, Argument::type(DateRange::class))->willThrow(
            InvalidArgumentException::class
        )->shouldBeCalledTimes(1);

        $response = $this->action->handle(ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode));
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function unexpectedExceptionWillReturnError()
    {
        $shortCode = 'abc123';
        $this->visitsTracker->info($shortCode, Argument::type(DateRange::class))->willThrow(
            \Exception::class
        )->shouldBeCalledTimes(1);

        $response = $this->action->handle(ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode));
        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function datesAreReadFromQuery()
    {
        $shortCode = 'abc123';
        $this->visitsTracker->info($shortCode, new DateRange(null, new \DateTime('2016-01-01 00:00:00')))
            ->willReturn([])
            ->shouldBeCalledTimes(1);

        $response = $this->action->handle(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode)
                                               ->withQueryParams(['endDate' => '2016-01-01 00:00:00'])
        );
        $this->assertEquals(200, $response->getStatusCode());
    }
}
