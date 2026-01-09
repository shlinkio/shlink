<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\ShortUrl;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware;

class CreateShortUrlContentNegotiationMiddlewareTest extends TestCase
{
    private CreateShortUrlContentNegotiationMiddleware $middleware;
    private MockObject & RequestHandlerInterface $requestHandler;

    protected function setUp(): void
    {
        $this->middleware = new CreateShortUrlContentNegotiationMiddleware();
        $this->requestHandler = $this->createMock(RequestHandlerInterface::class);
    }

    #[Test]
    public function whenNoJsonResponseIsReturnedNoFurtherOperationsArePerformed(): void
    {
        $expectedResp = new Response();
        $this->requestHandler->expects($this->once())->method('handle')->willReturn($expectedResp);

        $resp = $this->middleware->process(new ServerRequest(), $this->requestHandler);

        self::assertSame($expectedResp, $resp);
    }

    #[Test, DataProvider('provideData')]
    public function properResponseIsReturned(string|null $accept, array $query, string $expectedContentType): void
    {
        $request = new ServerRequest()->withQueryParams($query);
        if ($accept !== null) {
            $request = $request->withHeader('Accept', $accept);
        }

        $this->requestHandler->expects($this->once())->method('handle')->with(
            $this->isInstanceOf(ServerRequestInterface::class),
        )->willReturn(new JsonResponse(['shortUrl' => 'http://s.test/foo']));

        $response = $this->middleware->process($request, $this->requestHandler);

        self::assertEquals($expectedContentType, $response->getHeaderLine('Content-type'));
    }

    public static function provideData(): iterable
    {
        yield [null, [], 'application/json'];
        yield [null, ['format' => 'json'], 'application/json'];
        yield [null, ['format' => 'invalid'], 'application/json'];
        yield [null, ['format' => 'txt'], 'text/plain'];
        yield ['application/json', [], 'application/json'];
        yield ['application/xml', [], 'application/json'];
        yield ['text/plain', [], 'text/plain'];
        yield ['application/json', ['format' => 'txt'], 'text/plain'];
    }

    #[Test, DataProvider('provideTextBodies')]
    public function properBodyIsReturnedInPlainTextResponses(array $json, string $expectedBody): void
    {
        $request = (new ServerRequest())->withQueryParams(['format' => 'txt']);

        $this->requestHandler->expects($this->once())->method('handle')->with(
            $this->isInstanceOf(ServerRequestInterface::class),
        )->willReturn(new JsonResponse($json));

        $response = $this->middleware->process($request, $this->requestHandler);

        self::assertEquals($expectedBody, (string) $response->getBody());
    }

    public static function provideTextBodies(): iterable
    {
        yield 'shortUrl key' => [['shortUrl' => 'foobar'], 'foobar'];
        yield 'error key' => [['error' => 'FOO_BAR'], 'FOO_BAR'];
        yield 'no shortUrl or error keys' => [[], ''];
    }
}
