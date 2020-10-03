<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\Visit\GlobalVisitsAction;

class GlobalVisitsActionTest extends TestCase
{
    private GlobalVisitsAction $action;
    private ObjectProphecy $helper;

    public function setUp(): void
    {
        $this->helper = $this->prophesize(VisitsStatsHelperInterface::class);
        $this->action = new GlobalVisitsAction($this->helper->reveal());
    }

    /** @test */
    public function statsAreReturnedFromHelper(): void
    {
        $stats = new VisitsStats(5);
        $getStats = $this->helper->getVisitsStats()->willReturn($stats);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(ServerRequestFactory::fromGlobals());
        $payload = $resp->getPayload();

        self::assertEquals($payload, ['visits' => $stats]);
        $getStats->shouldHaveBeenCalledOnce();
    }
}
