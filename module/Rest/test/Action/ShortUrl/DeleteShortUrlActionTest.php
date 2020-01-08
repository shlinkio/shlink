<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Service\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\ShortUrl\DeleteShortUrlAction;

class DeleteShortUrlActionTest extends TestCase
{
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
        $deleteByShortCode = $this->service->deleteByShortCode(Argument::any())->will(function (): void {
        });

        $resp = $this->action->handle(new ServerRequest());

        $this->assertEquals(204, $resp->getStatusCode());
        $deleteByShortCode->shouldHaveBeenCalledOnce();
    }
}
