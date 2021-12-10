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
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\Visit\OrphanVisitsAction;

use function count;

class OrphanVisitsActionTest extends TestCase
{
    use ProphecyTrait;

    private OrphanVisitsAction $action;
    private ObjectProphecy $visitsHelper;
    private ObjectProphecy $orphanVisitTransformer;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->prophesize(VisitsStatsHelperInterface::class);
        $this->orphanVisitTransformer = $this->prophesize(DataTransformerInterface::class);

        $this->action = new OrphanVisitsAction($this->visitsHelper->reveal(), $this->orphanVisitTransformer->reveal());
    }

    /** @test */
    public function requestIsHandled(): void
    {
        $visitor = Visitor::emptyInstance();
        $visits = [Visit::forInvalidShortUrl($visitor), Visit::forRegularNotFound($visitor)];
        $orphanVisits = $this->visitsHelper->orphanVisits(Argument::type(VisitsParams::class))->willReturn(
            new Paginator(new ArrayAdapter($visits)),
        );
        $visitsAmount = count($visits);
        $transform = $this->orphanVisitTransformer->transform(Argument::type(Visit::class))->willReturn([]);

        /** @var JsonResponse $response */
        $response = $this->action->handle(ServerRequestFactory::fromGlobals());
        $payload = $response->getPayload();

        self::assertCount($visitsAmount, $payload['visits']['data']);
        self::assertEquals(200, $response->getStatusCode());
        $orphanVisits->shouldHaveBeenCalledOnce();
        $transform->shouldHaveBeenCalledTimes($visitsAmount);
    }
}
