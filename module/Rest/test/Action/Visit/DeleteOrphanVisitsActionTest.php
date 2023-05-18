<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Model\BulkDeleteResult;
use Shlinkio\Shlink\Core\Visit\VisitsDeleterInterface;
use Shlinkio\Shlink\Rest\Action\Visit\DeleteOrphanVisitsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DeleteOrphanVisitsActionTest extends TestCase
{
    private DeleteOrphanVisitsAction $action;
    private MockObject & VisitsDeleterInterface $deleter;

    protected function setUp(): void
    {
        $this->deleter = $this->createMock(VisitsDeleterInterface::class);
        $this->action = new DeleteOrphanVisitsAction($this->deleter);
    }

    #[Test, DataProvider('provideVisitsCounts')]
    public function orphanVisitsAreDeleted(int $visitsCount): void
    {
        $apiKey = ApiKey::create();
        $request = ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $apiKey);

        $this->deleter->expects($this->once())->method('deleteOrphanVisits')->with($apiKey)->willReturn(
            new BulkDeleteResult($visitsCount),
        );

        /** @var JsonResponse $resp */
        $resp = $this->action->handle($request);
        $payload = $resp->getPayload();

        self::assertEquals(['deletedVisits' => $visitsCount], $payload);
    }

    public static function provideVisitsCounts(): iterable
    {
        yield '1' => [1];
        yield '0' => [0];
        yield '300' => [300];
        yield '1234' => [1234];
    }
}
