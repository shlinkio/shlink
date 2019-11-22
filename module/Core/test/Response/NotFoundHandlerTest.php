<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Response;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Core\Response\NotFoundHandler;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Template\TemplateRendererInterface;

class NotFoundHandlerTest extends TestCase
{
    /** @var NotFoundHandler */
    private $delegate;
    /** @var ObjectProphecy */
    private $renderer;
    /** @var NotFoundRedirectOptions */
    private $redirectOptions;

    public function setUp(): void
    {
        $this->renderer = $this->prophesize(TemplateRendererInterface::class);
        $this->redirectOptions = new NotFoundRedirectOptions();

        $this->delegate = new NotFoundHandler($this->renderer->reveal(), $this->redirectOptions, '');
    }

    /**
     * @test
     * @dataProvider provideRedirects
     */
    public function expectedRedirectionIsReturnedDependingOnTheCase(
        ServerRequestInterface $request,
        string $expectedRedirectTo
    ): void {
        $this->redirectOptions->invalidShortUrl = 'invalidShortUrl';
        $this->redirectOptions->regular404 = 'regular404';
        $this->redirectOptions->baseUrl = 'baseUrl';

        $resp = $this->delegate->handle($request);

        $this->assertInstanceOf(Response\RedirectResponse::class, $resp);
        $this->assertEquals($expectedRedirectTo, $resp->getHeaderLine('Location'));
        $this->renderer->render(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function provideRedirects(): iterable
    {
        yield 'base URL with trailing slash' => [
            ServerRequestFactory::fromGlobals()->withUri(new Uri('/')),
            'baseUrl',
        ];
        yield 'base URL without trailing slash' => [
            ServerRequestFactory::fromGlobals()->withUri(new Uri('')),
            'baseUrl',
        ];
        yield 'regular 404' => [
            ServerRequestFactory::fromGlobals()->withUri(new Uri('/foo/bar')),
            'regular404',
        ];
        yield 'invalid short URL' => [
            ServerRequestFactory::fromGlobals()
                ->withAttribute(
                    RouteResult::class,
                    RouteResult::fromRoute(
                        new Route(
                            '',
                            $this->prophesize(MiddlewareInterface::class)->reveal(),
                            ['GET'],
                            RedirectAction::class
                        )
                    )
                )
                ->withUri(new Uri('/abc123')),
            'invalidShortUrl',
        ];
    }

    /**
     * @test
     * @dataProvider provideTemplates
     */
    public function properErrorTemplateIsRendered(ServerRequestInterface $request, string $expectedTemplate): void
    {
        $request = $request->withHeader('Accept', 'text/html');
        $render = $this->renderer->render($expectedTemplate)->willReturn('');

        $resp = $this->delegate->handle($request);

        $this->assertInstanceOf(Response\HtmlResponse::class, $resp);
        $render->shouldHaveBeenCalledOnce();
    }

    public function provideTemplates(): iterable
    {
        $request = ServerRequestFactory::fromGlobals();

        yield [$request, NotFoundHandler::NOT_FOUND_TEMPLATE];
        yield [
            $request->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(new Route('', $this->prophesize(MiddlewareInterface::class)->reveal()))
            ),
            NotFoundHandler::INVALID_SHORT_CODE_TEMPLATE,
        ];
    }
}
