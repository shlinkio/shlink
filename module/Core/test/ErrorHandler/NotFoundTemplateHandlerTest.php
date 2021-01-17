<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ErrorHandler;

use Closure;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Shlinkio\Shlink\Core\ErrorHandler\NotFoundTemplateHandler;

class NotFoundTemplateHandlerTest extends TestCase
{
    private NotFoundTemplateHandler $handler;
    private Closure $readFile;
    private bool $readFileCalled;

    public function setUp(): void
    {
        $this->readFileCalled = false;
        $this->readFile = function (string $fileName): string {
            $this->readFileCalled = true;
            return $fileName;
        };
        $this->handler = new NotFoundTemplateHandler($this->readFile);
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
