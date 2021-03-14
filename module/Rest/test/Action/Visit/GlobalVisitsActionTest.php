<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\Visit\GlobalVisitsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class GlobalVisitsActionTest extends TestCase
{
    use ProphecyTrait;

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
        $apiKey = ApiKey::create();
        $stats = new VisitsStats(5, 3);
        $getStats = $this->helper->getVisitsStats($apiKey)->willReturn($stats);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $apiKey));
        $payload = $resp->getPayload();

        self::assertEquals($payload, ['visits' => $stats]);
        $getStats->shouldHaveBeenCalledOnce();
    }
}
