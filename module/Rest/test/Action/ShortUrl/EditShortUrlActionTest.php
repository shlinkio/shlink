<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\ShortUrl\EditShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class EditShortUrlActionTest extends TestCase
{
    use ProphecyTrait;

    private EditShortUrlAction $action;
    private ObjectProphecy $shortUrlService;

    public function setUp(): void
    {
        $this->shortUrlService = $this->prophesize(ShortUrlServiceInterface::class);
        $this->action = new EditShortUrlAction($this->shortUrlService->reveal(), new ShortUrlDataTransformer(
            new ShortUrlStringifier([]),
        ));
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
                                        ->withAttribute(ApiKey::class, ApiKey::create())
                                        ->withParsedBody([
                                            'maxVisits' => 5,
                                        ]);
        $updateMeta = $this->shortUrlService->updateShortUrl(Argument::cetera())->willReturn(
            ShortUrl::createEmpty(),
        );

        $resp = $this->action->handle($request);

        self::assertEquals(200, $resp->getStatusCode());
        $updateMeta->shouldHaveBeenCalled();
    }
}
