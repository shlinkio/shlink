<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\Visit\NonOrphanVisitsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class NonOrphanVisitsActionTest extends TestCase
{
    use ProphecyTrait;

    private NonOrphanVisitsAction $action;
    private ObjectProphecy $visitsHelper;

    public function setUp(): void
    {
        $this->visitsHelper = $this->prophesize(VisitsStatsHelperInterface::class);
        $this->action = new NonOrphanVisitsAction($this->visitsHelper->reveal());
    }

    /** @test */
    public function requestIsHandled(): void
    {
        $apiKey = ApiKey::create();
        $getVisits = $this->visitsHelper->nonOrphanVisits(Argument::type(VisitsParams::class), $apiKey)->willReturn(
            new Paginator(new ArrayAdapter([])),
        );

        /** @var JsonResponse $response */
        $response = $this->action->handle(ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $apiKey));
        $payload = $response->getPayload();

        self::assertEquals(200, $response->getStatusCode());
        self::assertArrayHasKey('visits', $payload);
        $getVisits->shouldHaveBeenCalledOnce();
    }
}
