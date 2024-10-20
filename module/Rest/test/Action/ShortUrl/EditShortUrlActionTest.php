<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\ShortUrl\EditShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class EditShortUrlActionTest extends TestCase
{
    private EditShortUrlAction $action;
    private MockObject & ShortUrlServiceInterface $shortUrlService;

    protected function setUp(): void
    {
        $this->shortUrlService = $this->createMock(ShortUrlServiceInterface::class);
        $this->action = new EditShortUrlAction($this->shortUrlService, new ShortUrlDataTransformer(
            new ShortUrlStringifier(),
        ));
    }

    #[Test]
    public function invalidDataThrowsError(): void
    {
        $request = (new ServerRequest())->withParsedBody([
            'maxVisits' => 'invalid',
        ]);
        $this->shortUrlService->expects($this->never())->method('updateShortUrl');

        $this->expectException(ValidationException::class);

        $this->action->handle($request);
    }

    #[Test]
    public function correctShortCodeReturnsSuccess(): void
    {
        $request = (new ServerRequest())->withAttribute('shortCode', 'abc123')
                                        ->withAttribute(ApiKey::class, ApiKey::create())
                                        ->withParsedBody([
                                            'maxVisits' => 5,
                                        ]);
        $this->shortUrlService->expects($this->once())->method('updateShortUrl')->willReturn(ShortUrl::createFake());

        $resp = $this->action->handle($request);

        self::assertEquals(200, $resp->getStatusCode());
    }
}
