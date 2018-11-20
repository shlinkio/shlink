<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequestFactory;

class CreateShortUrlContentNegotiationMiddlewareTest extends TestCase
{
    /** @var CreateShortUrlContentNegotiationMiddleware */
    private $middleware;
    /** @var RequestHandlerInterface */
    private $requestHandler;

    public function setUp()
    {
        $this->middleware = new CreateShortUrlContentNegotiationMiddleware();
        $this->requestHandler = $this->prophesize(RequestHandlerInterface::class);
    }

    /**
     * @test
     */
    public function whenNoJsonResponseIsReturnedNoFurtherOperationsArePerformed()
    {
        $expectedResp = new Response();
        $this->requestHandler->handle(Argument::type(ServerRequestInterface::class))->willReturn($expectedResp);

        $resp = $this->middleware->process(ServerRequestFactory::fromGlobals(), $this->requestHandler->reveal());

        $this->assertSame($expectedResp, $resp);
    }

    /**
     * @test
     * @dataProvider provideData
     * @param array $query
     */
    public function properResponseIsReturned(?string $accept, array $query, string $expectedContentType)
    {
        $request = ServerRequestFactory::fromGlobals()->withQueryParams($query);
        if ($accept !== null) {
            $request = $request->withHeader('Accept', $accept);
        }

        $handle = $this->requestHandler->handle(Argument::type(ServerRequestInterface::class))->willReturn(
            new JsonResponse(['shortUrl' => 'http://doma.in/foo'])
        );

        $response = $this->middleware->process($request, $this->requestHandler->reveal());

        $this->assertEquals($expectedContentType, $response->getHeaderLine('Content-type'));
        $handle->shouldHaveBeenCalled();
    }

    public function provideData(): array
    {
        return [
            [null, [], 'application/json'],
            [null, ['format' => 'json'], 'application/json'],
            [null, ['format' => 'invalid'], 'application/json'],
            [null, ['format' => 'txt'], 'text/plain'],
            ['application/json', [], 'application/json'],
            ['application/xml', [], 'application/json'],
            ['text/plain', [], 'text/plain'],
            ['application/json', ['format' => 'txt'], 'text/plain'],
        ];
    }

    /**
     * @test
     * @dataProvider provideTextBodies
     * @param array $json
     */
    public function properBodyIsReturnedInPlainTextResponses(array $json, string $expectedBody)
    {
        $request = ServerRequestFactory::fromGlobals()->withQueryParams(['format' => 'txt']);

        $handle = $this->requestHandler->handle(Argument::type(ServerRequestInterface::class))->willReturn(
            new JsonResponse($json)
        );

        $response = $this->middleware->process($request, $this->requestHandler->reveal());

        $this->assertEquals($expectedBody, (string) $response->getBody());
        $handle->shouldHaveBeenCalled();
    }

    public function provideTextBodies(): array
    {
        return [
            [['shortUrl' => 'foobar'], 'foobar'],
            [['error' => 'FOO_BAR'], 'FOO_BAR'],
            [[], ''],
        ];
    }
}
