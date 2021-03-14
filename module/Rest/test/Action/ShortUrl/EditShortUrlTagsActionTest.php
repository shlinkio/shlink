<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlEdit;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\Rest\Action\ShortUrl\EditShortUrlTagsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class EditShortUrlTagsActionTest extends TestCase
{
    use ProphecyTrait;

    private EditShortUrlTagsAction $action;
    private ObjectProphecy $shortUrlService;

    public function setUp(): void
    {
        $this->shortUrlService = $this->prophesize(ShortUrlService::class);
        $this->action = new EditShortUrlTagsAction($this->shortUrlService->reveal());
    }

    /** @test */
    public function notProvidingTagsReturnsError(): void
    {
        $this->expectException(ValidationException::class);
        $this->action->handle($this->createRequestWithAPiKey()->withAttribute('shortCode', 'abc123'));
    }

    /** @test */
    public function tagsListIsReturnedIfCorrectShortCodeIsProvided(): void
    {
        $shortCode = 'abc123';
        $this->shortUrlService->updateShortUrl(
            new ShortUrlIdentifier($shortCode),
            Argument::type(ShortUrlEdit::class),
            Argument::type(ApiKey::class),
        )->willReturn(ShortUrl::createEmpty())
         ->shouldBeCalledOnce();

        $response = $this->action->handle(
            $this->createRequestWithAPiKey()->withAttribute('shortCode', 'abc123')
                                            ->withParsedBody(['tags' => []]),
        );
        self::assertEquals(200, $response->getStatusCode());
    }

    private function createRequestWithAPiKey(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, ApiKey::create());
    }
}
