<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\ShortCode;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Middleware\ShortCode\CreateShortCodeContentNegotiationMiddleware;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequestFactory;

class CreateShortCodeContentNegotiationMiddlewareTest extends TestCase
{
    /**
     * @var CreateShortCodeContentNegotiationMiddleware
     */
    private $middleware;
    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;

    public function setUp()
    {
        $this->middleware = new CreateShortCodeContentNegotiationMiddleware();
        $this->requestHandler = new class implements RequestHandlerInterface {
            /**
             * Handle the request and return a response.
             */
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new JsonResponse(['shortUrl' => 'http://doma.in/foo']);
            }
        };
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

        $response = $this->middleware->process($request, $this->requestHandler);

        $this->assertEquals($expectedContentType, $response->getHeaderLine('Content-type'));
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
        ];
    }
}
