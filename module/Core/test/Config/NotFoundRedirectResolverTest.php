<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectResolver;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;

class NotFoundRedirectResolverTest extends TestCase
{
    private NotFoundRedirectResolver $resolver;
    private MockObject $helper;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(RedirectResponseHelperInterface::class);
        $this->resolver = new NotFoundRedirectResolver($this->helper, new NullLogger());
    }

    /**
     * @test
     * @dataProvider provideRedirects
     */
    public function expectedRedirectionIsReturnedDependingOnTheCase(
        UriInterface $uri,
        NotFoundType $notFoundType,
        NotFoundRedirectOptions $redirectConfig,
        string $expectedRedirectTo,
    ): void {
        $expectedResp = new Response();
        $this->helper->expects($this->once())->method('buildRedirectResponse')->with(
            $this->equalTo($expectedRedirectTo),
        )->willReturn($expectedResp);

        $resp = $this->resolver->resolveRedirectResponse($notFoundType, $redirectConfig, $uri);

        self::assertSame($expectedResp, $resp);
    }

    public function provideRedirects(): iterable
    {
        yield 'base URL with trailing slash' => [
            $uri = new Uri('/'),
            $this->notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(baseUrl: 'baseUrl'),
            'baseUrl',
        ];
        yield 'base URL with domain placeholder' => [
            $uri = new Uri('https://doma.in'),
            $this->notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(baseUrl: 'https://redirect-here.com/{DOMAIN}'),
            'https://redirect-here.com/doma.in',
        ];
        yield 'base URL with domain placeholder in query' => [
            $uri = new Uri('https://doma.in'),
            $this->notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(baseUrl: 'https://redirect-here.com/?domain={DOMAIN}'),
            'https://redirect-here.com/?domain=doma.in',
        ];
        yield 'base URL without trailing slash' => [
            $uri = new Uri(''),
            $this->notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(baseUrl: 'baseUrl'),
            'baseUrl',
        ];
        yield 'regular 404' => [
            $uri = new Uri('/foo/bar'),
            $this->notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(regular404: 'regular404'),
            'regular404',
        ];
        yield 'regular 404 with path placeholder in query' => [
            $uri = new Uri('/foo/bar'),
            $this->notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(regular404: 'https://redirect-here.com/?path={ORIGINAL_PATH}'),
            'https://redirect-here.com/?path=%2Ffoo%2Fbar',
        ];
        yield 'regular 404 with multiple placeholders' => [
            $uri = new Uri('https://doma.in/foo/bar'),
            $this->notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(
                regular404: 'https://redirect-here.com/{ORIGINAL_PATH}/{DOMAIN}/?d={DOMAIN}&p={ORIGINAL_PATH}',
            ),
            'https://redirect-here.com/foo/bar/doma.in/?d=doma.in&p=%2Ffoo%2Fbar',
        ];
        yield 'invalid short URL' => [
            new Uri('/foo'),
            $this->notFoundType($this->requestForRoute(RedirectAction::class)),
            new NotFoundRedirectOptions(invalidShortUrl: 'invalidShortUrl'),
            'invalidShortUrl',
        ];
        yield 'invalid short URL with path placeholder' => [
            new Uri('/foo'),
            $this->notFoundType($this->requestForRoute(RedirectAction::class)),
            new NotFoundRedirectOptions(invalidShortUrl: 'https://redirect-here.com/{ORIGINAL_PATH}'),
            'https://redirect-here.com/foo',
        ];
    }

    /** @test */
    public function noResponseIsReturnedIfNoConditionsMatch(): void
    {
        $notFoundType = $this->notFoundType($this->requestForRoute('foo'));
        $this->helper->expects($this->never())->method('buildRedirectResponse');

        $result = $this->resolver->resolveRedirectResponse($notFoundType, new NotFoundRedirectOptions(), new Uri());

        self::assertNull($result);
    }

    private function notFoundType(ServerRequestInterface $req): NotFoundType
    {
        return NotFoundType::fromRequest($req, '');
    }

    private function requestForRoute(string $routeName): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals()
            ->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(
                    new Route(
                        '',
                        $this->createMock(MiddlewareInterface::class),
                        ['GET'],
                        $routeName,
                    ),
                ),
            )
            ->withUri(new Uri('/abc123'));
    }
}
