<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Model\BulkDeleteResult;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlVisitsDeleterInterface;
use Shlinkio\Shlink\Rest\Action\ShortUrl\DeleteShortUrlVisitsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DeleteShortUrlVisitsActionTest extends TestCase
{
    private DeleteShortUrlVisitsAction $action;
    private MockObject & ShortUrlVisitsDeleterInterface $deleter;

    protected function setUp(): void
    {
        $this->deleter = $this->createMock(ShortUrlVisitsDeleterInterface::class);
        $this->action = new DeleteShortUrlVisitsAction($this->deleter);
    }

    #[Test, DataProvider('provideVisitsCounts')]
    public function visitsAreDeletedForShortUrl(int $visitsCount): void
    {
        $apiKey = ApiKey::create();
        $request = ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $apiKey)
                                                      ->withAttribute('shortCode', 'foo');

        $this->deleter->expects($this->once())->method('deleteShortUrlVisits')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain('foo'),
            $apiKey,
        )->willReturn(new BulkDeleteResult($visitsCount));

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
