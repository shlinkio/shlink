<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\Visit\OrphanVisitsAction;

use function count;

class OrphanVisitsActionTest extends TestCase
{
    private OrphanVisitsAction $action;
    private MockObject $visitsHelper;
    private MockObject $orphanVisitTransformer;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->createMock(VisitsStatsHelperInterface::class);
        $this->orphanVisitTransformer = $this->createMock(DataTransformerInterface::class);

        $this->action = new OrphanVisitsAction($this->visitsHelper, $this->orphanVisitTransformer);
    }

    /** @test */
    public function requestIsHandled(): void
    {
        $visitor = Visitor::emptyInstance();
        $visits = [Visit::forInvalidShortUrl($visitor), Visit::forRegularNotFound($visitor)];
        $this->visitsHelper->expects($this->once())->method('orphanVisits')->with(
            $this->isInstanceOf(VisitsParams::class),
        )->willReturn(new Paginator(new ArrayAdapter($visits)));
        $visitsAmount = count($visits);
        $this->orphanVisitTransformer->expects($this->exactly($visitsAmount))->method('transform')->with(
            $this->isInstanceOf(Visit::class),
        )->willReturn([]);

        /** @var JsonResponse $response */
        $response = $this->action->handle(ServerRequestFactory::fromGlobals());
        $payload = $response->getPayload();

        self::assertCount($visitsAmount, $payload['visits']['data']);
        self::assertEquals(200, $response->getStatusCode());
    }
}
