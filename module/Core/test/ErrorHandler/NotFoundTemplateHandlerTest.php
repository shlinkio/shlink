<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ErrorHandler;

use Closure;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\ErrorHandler\NotFoundTemplateHandler;

class NotFoundTemplateHandlerTest extends TestCase
{
    private NotFoundTemplateHandler $handler;
    private bool $readFileCalled;

    public function setUp(): void
    {
        $this->readFileCalled = false;
        $readFile = function (string $fileName): string {
            $this->readFileCalled = true;
            return $fileName;
        };
        $this->handler = new NotFoundTemplateHandler($readFile);
    }

    /**
     * @test
     * @dataProvider provideTemplates
     */
    public function properErrorTemplateIsRendered(ServerRequestInterface $request, string $expectedTemplate): void
    {
        $resp = $this->handler->handle($request->withHeader('Accept', 'text/html'));

        self::assertInstanceOf(Response\HtmlResponse::class, $resp);
        self::assertStringContainsString($expectedTemplate, (string) $resp->getBody());
        self::assertTrue($this->readFileCalled);
    }

    public function provideTemplates(): iterable
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('/foo'));

        yield 'base url' => [$this->withNotFoundType($request, '/foo'), NotFoundTemplateHandler::NOT_FOUND_TEMPLATE];
        yield 'regular not found' => [$this->withNotFoundType($request), NotFoundTemplateHandler::NOT_FOUND_TEMPLATE];
        yield 'invalid short code' => [
            $this->withNotFoundType($request->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(
                    new Route(
                        '',
                        $this->prophesize(MiddlewareInterface::class)->reveal(),
                        ['GET'],
                        RedirectAction::class,
                    ),
                ),
            )),
            NotFoundTemplateHandler::INVALID_SHORT_CODE_TEMPLATE,
        ];
    }

    private function withNotFoundType(ServerRequestInterface $req, string $baseUrl = ''): ServerRequestInterface
    {
        $type = NotFoundType::fromRequest($req, $baseUrl);
        return $req->withAttribute(NotFoundType::class, $type);
    }
}
