<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ErrorHandler;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Shlinkio\Shlink\Core\ErrorHandler\NotFoundTemplateHandler;

class NotFoundTemplateHandlerTest extends TestCase
{
    private NotFoundTemplateHandler $handler;
    private ObjectProphecy $renderer;

    public function setUp(): void
    {
        $this->renderer = $this->prophesize(TemplateRendererInterface::class);
        $this->handler = new NotFoundTemplateHandler($this->renderer->reveal());
    }

    /**
     * @test
     * @dataProvider provideTemplates
     */
    public function properErrorTemplateIsRendered(ServerRequestInterface $request, string $expectedTemplate): void
    {
        $request = $request->withHeader('Accept', 'text/html');
        $render = $this->renderer->render($expectedTemplate)->willReturn('');

        $resp = $this->handler->handle($request);

        $this->assertInstanceOf(Response\HtmlResponse::class, $resp);
        $render->shouldHaveBeenCalledOnce();
    }

    public function provideTemplates(): iterable
    {
        $request = ServerRequestFactory::fromGlobals();

        yield [$request, NotFoundTemplateHandler::NOT_FOUND_TEMPLATE];
        yield [
            $request->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(new Route('', $this->prophesize(MiddlewareInterface::class)->reveal())),
            ),
            NotFoundTemplateHandler::INVALID_SHORT_CODE_TEMPLATE,
        ];
    }
}
