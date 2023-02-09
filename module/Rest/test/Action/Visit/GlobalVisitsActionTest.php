<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\Visit\GlobalVisitsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class GlobalVisitsActionTest extends TestCase
{
    private GlobalVisitsAction $action;
    private MockObject & VisitsStatsHelperInterface $helper;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(VisitsStatsHelperInterface::class);
        $this->action = new GlobalVisitsAction($this->helper);
    }

    #[Test]
    public function statsAreReturnedFromHelper(): void
    {
        $apiKey = ApiKey::create();
        $stats = new VisitsStats(5, 3);
        $this->helper->expects($this->once())->method('getVisitsStats')->with($apiKey)->willReturn($stats);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $apiKey));
        $payload = $resp->getPayload();

        self::assertEquals($payload, ['visits' => $stats]);
    }
}
