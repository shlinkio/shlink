<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\Visit\NonOrphanVisitsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class NonOrphanVisitsActionTest extends TestCase
{
    private NonOrphanVisitsAction $action;
    private MockObject $visitsHelper;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->createMock(VisitsStatsHelperInterface::class);
        $this->action = new NonOrphanVisitsAction($this->visitsHelper);
    }

    /** @test */
    public function requestIsHandled(): void
    {
        $apiKey = ApiKey::create();
        $this->visitsHelper->expects($this->once())->method('nonOrphanVisits')->with(
            $this->isInstanceOf(VisitsParams::class),
            $apiKey,
        )->willReturn(new Paginator(new ArrayAdapter([])));

        /** @var JsonResponse $response */
        $response = $this->action->handle(ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $apiKey));
        $payload = $response->getPayload();

        self::assertEquals(200, $response->getStatusCode());
        self::assertArrayHasKey('visits', $payload);
    }
}
