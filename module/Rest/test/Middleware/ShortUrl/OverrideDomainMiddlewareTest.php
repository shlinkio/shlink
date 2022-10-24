<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\ShortUrl;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Middleware\ShortUrl\OverrideDomainMiddleware;

class OverrideDomainMiddlewareTest extends TestCase
{
    private OverrideDomainMiddleware $middleware;
    private MockObject & DomainServiceInterface $domainService;
    private MockObject & ApiKey $apiKey;
    private MockObject & RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->apiKey = $this->createMock(ApiKey::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);

        $this->domainService = $this->createMock(DomainServiceInterface::class);
        $this->middleware = new OverrideDomainMiddleware($this->domainService);
    }

    /** @test */
    public function nextMiddlewareIsCalledWhenApiKeyDoesNotHaveProperRole(): void
    {
        $request = $this->requestWithApiKey();
        $response = new Response();
        $this->apiKey->expects($this->once())->method('hasRole')->with(Role::DOMAIN_SPECIFIC)->willReturn(false);
        $this->handler->expects($this->once())->method('handle')->with($request)->willReturn($response);
        $this->domainService->expects($this->never())->method('getDomain');

        $result = $this->middleware->process($request, $this->handler);

        self::assertSame($response, $result);
    }

    /**
     * @test
     * @dataProvider provideBodies
     */
    public function overwritesRequestBodyWhenMethodIsPost(Domain $domain, array $body, array $expectedBody): void
    {
        $request = $this->requestWithApiKey()->withMethod('POST')->withParsedBody($body);
        $this->apiKey->expects($this->once())->method('hasRole')->with(Role::DOMAIN_SPECIFIC)->willReturn(true);
        $this->apiKey->expects($this->once())->method('getRoleMeta')->with(Role::DOMAIN_SPECIFIC)->willReturn(
            ['domain_id' => '123'],
        );
        $this->domainService->expects($this->once())->method('getDomain')->with('123')->willReturn($domain);
        $this->handler->expects($this->once())->method('handle')->with($this->callback(
            function (ServerRequestInterface $req) use ($expectedBody): bool {
                Assert::assertEquals($req->getParsedBody(), $expectedBody);
                return true;
            },
        ))->willReturn(new Response());

        $this->middleware->process($request, $this->handler);
    }

    public function provideBodies(): iterable
    {
        yield 'no domain provided' => [
            Domain::withAuthority('foo.com'),
            [],
            [ShortUrlInputFilter::DOMAIN => 'foo.com'],
        ];
        yield 'other domain provided' => [
            Domain::withAuthority('bar.com'),
            [ShortUrlInputFilter::DOMAIN => 'foo.com'],
            [ShortUrlInputFilter::DOMAIN => 'bar.com'],
        ];
        yield 'same domain provided' => [
            Domain::withAuthority('baz.com'),
            [ShortUrlInputFilter::DOMAIN => 'baz.com'],
            [ShortUrlInputFilter::DOMAIN => 'baz.com'],
        ];
        yield 'more body params' => [
            Domain::withAuthority('doma.in'),
            [ShortUrlInputFilter::DOMAIN => 'baz.com', 'something' => 'else', 'foo' => 123],
            [ShortUrlInputFilter::DOMAIN => 'doma.in', 'something' => 'else', 'foo' => 123],
        ];
    }

    /**
     * @test
     * @dataProvider provideMethods
     */
    public function setsRequestAttributeWhenMethodIsNotPost(string $method): void
    {
        $domain = Domain::withAuthority('something.com');
        $request = $this->requestWithApiKey()->withMethod($method);
        $this->apiKey->expects($this->once())->method('hasRole')->with(Role::DOMAIN_SPECIFIC)->willReturn(true);
        $this->apiKey->expects($this->once())->method('getRoleMeta')->with(Role::DOMAIN_SPECIFIC)->willReturn(
            ['domain_id' => '123'],
        );
        $this->domainService->expects($this->once())->method('getDomain')->with('123')->willReturn($domain);
        $this->handler->expects($this->once())->method('handle')->with($this->callback(
            function (ServerRequestInterface $req): bool {
                Assert::assertEquals($req->getAttribute(ShortUrlInputFilter::DOMAIN), 'something.com');
                return true;
            },
        ))->willReturn(new Response());

        $this->middleware->process($request, $this->handler);
    }

    public function provideMethods(): iterable
    {
        yield 'GET' => ['GET'];
        yield 'PUT' => ['PUT'];
        yield 'PATCH' => ['PATCH'];
        yield 'DELETE' => ['DELETE'];
    }

    private function requestWithApiKey(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $this->apiKey);
    }
}
