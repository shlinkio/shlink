<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\ShortUrl\EditShortUrlAction;
use Zend\Diactoros\ServerRequest;

class EditShortUrlActionTest extends TestCase
{
    private EditShortUrlAction $action;
    private ObjectProphecy $shortUrlService;

    public function setUp(): void
    {
        $this->shortUrlService = $this->prophesize(ShortUrlServiceInterface::class);
        $this->action = new EditShortUrlAction($this->shortUrlService->reveal());
    }

    /** @test */
    public function invalidDataThrowsError(): void
    {
        $request = (new ServerRequest())->withParsedBody([
            'maxVisits' => 'invalid',
        ]);

        $this->expectException(ValidationException::class);

        $this->action->handle($request);
    }

    /** @test */
    public function correctShortCodeReturnsSuccess(): void
    {
        $request = (new ServerRequest())->withAttribute('shortCode', 'abc123')
                                        ->withParsedBody([
                                            'maxVisits' => 5,
                                        ]);
        $updateMeta = $this->shortUrlService->updateMetadataByShortCode(Argument::cetera())->willReturn(
            new ShortUrl(''),
        );

        $resp = $this->action->handle($request);

        $this->assertEquals(204, $resp->getStatusCode());
        $updateMeta->shouldHaveBeenCalled();
    }
}
