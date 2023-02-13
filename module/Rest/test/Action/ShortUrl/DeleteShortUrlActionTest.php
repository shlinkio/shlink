<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\ShortUrl\DeleteShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DeleteShortUrlActionTest extends TestCase
{
    private DeleteShortUrlAction $action;
    private MockObject & DeleteShortUrlServiceInterface $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(DeleteShortUrlServiceInterface::class);
        $this->action = new DeleteShortUrlAction($this->service);
    }

    #[Test]
    public function emptyResponseIsReturnedIfProperlyDeleted(): void
    {
        $apiKey = ApiKey::create();
        $this->service->expects($this->once())->method('deleteByShortCode');

        $resp = $this->action->handle(ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $apiKey));

        self::assertEquals(204, $resp->getStatusCode());
    }
}
