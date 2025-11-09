<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ErrorHandler;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\ErrorHandler\ErrorTemplateHandler;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\ErrorHandler\NotFoundTemplateHandler;

use function Laminas\Stratigility\middleware;

class NotFoundTemplateHandlerTest extends TestCase
{
    private NotFoundTemplateHandler $handler;
    private bool $readFileCalled;

    protected function setUp(): void
    {
        $this->readFileCalled = false;
        $readFile = function (string $fileName): string {
            $this->readFileCalled = true;
            return $fileName;
        };
        $errorTemplateHandler = new ErrorTemplateHandler($readFile);
        $this->handler = new NotFoundTemplateHandler($errorTemplateHandler);
    }

    #[Test, DataProvider('provideTemplates')]
    public function properErrorTemplateIsRendered(ServerRequestInterface $request, string $expectedTemplate): void
    {
        $resp = $this->handler->handle($request->withHeader('Accept', 'text/html'));

        self::assertInstanceOf(Response\HtmlResponse::class, $resp);
        self::assertStringContainsString($expectedTemplate, (string) $resp->getBody());
        self::assertTrue($this->readFileCalled);
    }

    public static function provideTemplates(): iterable
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('/foo'));

        yield 'base url' => [self::withNotFoundType($request, '/foo'), ErrorTemplateHandler::ERROR_TEMPLATE];
        yield 'regular not found' => [self::withNotFoundType($request), ErrorTemplateHandler::ERROR_TEMPLATE];
        yield 'invalid short code' => [
            self::withNotFoundType($request->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(
                    new Route(
                        'foo',
                        middleware(function (): void {
                        }),
                        ['GET'],
                        RedirectAction::class,
                    ),
                ),
            )),
            ErrorTemplateHandler::ERROR_TEMPLATE,
        ];
    }

    private static function withNotFoundType(ServerRequestInterface $req, string $baseUrl = ''): ServerRequestInterface
    {
        $type = NotFoundType::fromRequest($req, $baseUrl);
        return $req->withAttribute(NotFoundType::class, $type);
    }
}
