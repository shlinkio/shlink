<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\Visit\OrphanVisitsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function count;

class OrphanVisitsActionTest extends TestCase
{
    private OrphanVisitsAction $action;
    private MockObject & VisitsStatsHelperInterface $visitsHelper;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->createMock(VisitsStatsHelperInterface::class);
        $this->action = new OrphanVisitsAction($this->visitsHelper);
    }

    #[Test]
    public function requestIsHandled(): void
    {
        $visitor = Visitor::emptyInstance();
        $visits = [Visit::forInvalidShortUrl($visitor), Visit::forRegularNotFound($visitor)];
        $this->visitsHelper->expects($this->once())->method('orphanVisits')->with(
            $this->isInstanceOf(OrphanVisitsParams::class),
        )->willReturn(new Paginator(new ArrayAdapter($visits)));
        $visitsAmount = count($visits);

        /** @var JsonResponse $response */
        $response = $this->action->handle(
            ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, ApiKey::create()),
        );
        $payload = $response->getPayload();

        self::assertCount($visitsAmount, $payload['visits']['data']);
        self::assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function exceptionIsThrownIfInvalidDataIsProvided(): void
    {
        $this->expectException(ValidationException::class);
        $this->action->handle(
            ServerRequestFactory::fromGlobals()
                ->withAttribute(ApiKey::class, ApiKey::create())
                ->withQueryParams(['type' => 'invalidType']),
        );
    }
}
