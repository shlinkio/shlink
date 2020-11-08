<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Common\Response\QrCodeResponse;
use Shlinkio\Shlink\Core\Action\QrCodeAction;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;

class QrCodeActionTest extends TestCase
{
    use ProphecyTrait;

    private QrCodeAction $action;
    private ObjectProphecy $urlResolver;

    public function setUp(): void
    {
        $router = $this->prophesize(RouterInterface::class);
        $router->generateUri(Argument::cetera())->willReturn('/foo/bar');

        $this->urlResolver = $this->prophesize(ShortUrlResolverInterface::class);

        $this->action = new QrCodeAction($this->urlResolver->reveal(), ['domain' => 'doma.in']);
    }

    /** @test */
    public function aNotFoundShortCodeWillDelegateIntoNextMiddleware(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode, ''))
            ->willThrow(ShortUrlNotFoundException::class)
            ->shouldBeCalledOnce();
        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $process = $delegate->handle(Argument::any())->willReturn(new Response());

        $this->action->process((new ServerRequest())->withAttribute('shortCode', $shortCode), $delegate->reveal());

        $process->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function anInvalidShortCodeWillReturnNotFoundResponse(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode, ''))
            ->willThrow(ShortUrlNotFoundException::class)
            ->shouldBeCalledOnce();
        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $process = $delegate->handle(Argument::any())->willReturn(new Response());

        $this->action->process((new ServerRequest())->withAttribute('shortCode', $shortCode), $delegate->reveal());

        $process->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function aCorrectRequestReturnsTheQrCodeResponse(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode, ''))
            ->willReturn(new ShortUrl(''))
            ->shouldBeCalledOnce();
        $delegate = $this->prophesize(RequestHandlerInterface::class);

        $resp = $this->action->process(
            (new ServerRequest())->withAttribute('shortCode', $shortCode),
            $delegate->reveal(),
        );

        self::assertInstanceOf(QrCodeResponse::class, $resp);
        self::assertEquals(200, $resp->getStatusCode());
        $delegate->handle(Argument::any())->shouldHaveBeenCalledTimes(0);
    }

    /**
     * @test
     * @dataProvider provideQueries
     */
    public function imageIsReturnedWithExpectedContentTypeBasedOnProvidedFormat(
        array $query,
        string $expectedContentType
    ): void {
        $code = 'abc123';
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($code, ''))->willReturn(new ShortUrl(''));
        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $req = (new ServerRequest())->withAttribute('shortCode', $code)->withQueryParams($query);

        $resp = $this->action->process($req, $delegate->reveal());

        self::assertEquals($expectedContentType, $resp->getHeaderLine('Content-Type'));
    }

    public function provideQueries(): iterable
    {
        yield 'no format' => [[], 'image/png'];
        yield 'png format' => [['format' => 'png'], 'image/png'];
        yield 'svg format' => [['format' => 'svg'], 'image/svg+xml'];
        yield 'unsupported format' => [['format' => 'jpg'], 'image/png'];
    }
}
