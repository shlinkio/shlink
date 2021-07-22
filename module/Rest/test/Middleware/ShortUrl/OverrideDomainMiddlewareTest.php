<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\ShortUrl;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Middleware\ShortUrl\OverrideDomainMiddleware;

class OverrideDomainMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private OverrideDomainMiddleware $middleware;
    private ObjectProphecy $domainService;
    private ObjectProphecy $apiKey;
    private ObjectProphecy $handler;

    protected function setUp(): void
    {
        $this->apiKey = $this->prophesize(ApiKey::class);
        $this->handler = $this->prophesize(RequestHandlerInterface::class);

        $this->domainService = $this->prophesize(DomainServiceInterface::class);
        $this->middleware = new OverrideDomainMiddleware($this->domainService->reveal());
    }

    /** @test */
    public function nextMiddlewareIsCalledWhenApiKeyDoesNotHaveProperRole(): void
    {
        $request = $this->requestWithApiKey();
        $response = new Response();
        $hasRole = $this->apiKey->hasRole(Role::DOMAIN_SPECIFIC)->willReturn(false);
        $handle = $this->handler->handle($request)->willReturn($response);
        $getDomain = $this->domainService->getDomain(Argument::cetera());

        $result = $this->middleware->process($request, $this->handler->reveal());

        self::assertSame($response, $result);
        $hasRole->shouldHaveBeenCalledOnce();
        $handle->shouldHaveBeenCalledOnce();
        $getDomain->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideBodies
     */
    public function overwritesRequestBodyWhenMethodIsPost(Domain $domain, array $body, array $expectedBody): void
    {
        $request = $this->requestWithApiKey()->withMethod('POST')->withParsedBody($body);
        $hasRole = $this->apiKey->hasRole(Role::DOMAIN_SPECIFIC)->willReturn(true);
        $getRoleMeta = $this->apiKey->getRoleMeta(Role::DOMAIN_SPECIFIC)->willReturn(['domain_id' => '123']);
        $getDomain = $this->domainService->getDomain('123')->willReturn($domain);
        $handle = $this->handler->handle(Argument::that(
            function (ServerRequestInterface $req) use ($expectedBody): bool {
                Assert::assertEquals($req->getParsedBody(), $expectedBody);
                return true;
            },
        ))->willReturn(new Response());

        $this->middleware->process($request, $this->handler->reveal());

        $hasRole->shouldHaveBeenCalledOnce();
        $getRoleMeta->shouldHaveBeenCalledOnce();
        $getDomain->shouldHaveBeenCalledOnce();
        $handle->shouldHaveBeenCalledOnce();
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
        $hasRole = $this->apiKey->hasRole(Role::DOMAIN_SPECIFIC)->willReturn(true);
        $getRoleMeta = $this->apiKey->getRoleMeta(Role::DOMAIN_SPECIFIC)->willReturn(['domain_id' => '123']);
        $getDomain = $this->domainService->getDomain('123')->willReturn($domain);
        $handle = $this->handler->handle(Argument::that(
            function (ServerRequestInterface $req): bool {
                Assert::assertEquals($req->getAttribute(ShortUrlInputFilter::DOMAIN), 'something.com');
                return true;
            },
        ))->willReturn(new Response());

        $this->middleware->process($request, $this->handler->reveal());

        $hasRole->shouldHaveBeenCalledOnce();
        $getRoleMeta->shouldHaveBeenCalledOnce();
        $getDomain->shouldHaveBeenCalledOnce();
        $handle->shouldHaveBeenCalledOnce();
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
        return ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $this->apiKey->reveal());
    }
}
