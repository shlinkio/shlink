<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Service\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\ShortUrl\DeleteShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DeleteShortUrlActionTest extends TestCase
{
    use ProphecyTrait;

    private DeleteShortUrlAction $action;
    private ObjectProphecy $service;

    public function setUp(): void
    {
        $this->service = $this->prophesize(DeleteShortUrlServiceInterface::class);
        $this->action = new DeleteShortUrlAction($this->service->reveal());
    }

    /** @test */
    public function emptyResponseIsReturnedIfProperlyDeleted(): void
    {
        $apiKey = ApiKey::create();
        $deleteByShortCode = $this->service->deleteByShortCode(Argument::any(), false, $apiKey)->will(
            function (): void {
            },
        );

        $resp = $this->action->handle(ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $apiKey));

        self::assertEquals(204, $resp->getStatusCode());
        $deleteByShortCode->shouldHaveBeenCalledOnce();
    }
}
