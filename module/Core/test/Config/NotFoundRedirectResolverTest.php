<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectResolver;
use Shlinkio\Shlink\Core\Config\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;

use function Laminas\Stratigility\middleware;

class NotFoundRedirectResolverTest extends TestCase
{
    private NotFoundRedirectResolver $resolver;
    private MockObject & RedirectResponseHelperInterface $helper;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(RedirectResponseHelperInterface::class);
        $this->resolver = new NotFoundRedirectResolver($this->helper, new NullLogger());
    }

    #[Test, DataProvider('provideRedirects')]
    public function expectedRedirectionIsReturnedDependingOnTheCase(
        UriInterface $uri,
        NotFoundType $notFoundType,
        NotFoundRedirectOptions $redirectConfig,
        string $expectedRedirectTo,
    ): void {
        $expectedResp = new Response();
        $this->helper->expects($this->once())->method('buildRedirectResponse')->with($expectedRedirectTo)->willReturn(
            $expectedResp,
        );

        $resp = $this->resolver->resolveRedirectResponse($notFoundType, $redirectConfig, $uri);

        self::assertSame($expectedResp, $resp);
    }

    public static function provideRedirects(): iterable
    {
        yield 'base URL with trailing slash' => [
            $uri = new Uri('/'),
            self::notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(baseUrlRedirect: 'https://example.com/baseUrl'),
            'https://example.com/baseUrl',
        ];
        yield 'base URL without trailing slash' => [
            $uri = new Uri(''),
            self::notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(baseUrlRedirect: 'https://example.com/baseUrl'),
            'https://example.com/baseUrl',
        ];
        yield 'base URL with domain placeholder' => [
            $uri = new Uri('https://s.test'),
            self::notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(baseUrlRedirect: 'https://redirect-here.com/{DOMAIN}'),
            'https://redirect-here.com/s.test',
        ];
        yield 'base URL with domain placeholder in query' => [
            $uri = new Uri('https://s.test'),
            self::notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(baseUrlRedirect: 'https://redirect-here.com/?domain={DOMAIN}'),
            'https://redirect-here.com/?domain=s.test',
        ];
        yield 'regular 404' => [
            $uri = new Uri('/foo/bar'),
            self::notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(regular404Redirect: 'https://example.com/regular404'),
            'https://example.com/regular404',
        ];
        yield 'regular 404 with path placeholder in query' => [
            $uri = new Uri('/foo/bar'),
            self::notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(regular404Redirect: 'https://redirect-here.com/?path={ORIGINAL_PATH}'),
            'https://redirect-here.com/?path=%2Ffoo%2Fbar',
        ];
        yield 'regular 404 with multiple placeholders' => [
            $uri = new Uri('https://s.test/foo/bar'),
            self::notFoundType(ServerRequestFactory::fromGlobals()->withUri($uri)),
            new NotFoundRedirectOptions(
                regular404Redirect: 'https://redirect-here.com/{ORIGINAL_PATH}/{DOMAIN}/?d={DOMAIN}&p={ORIGINAL_PATH}',
            ),
            'https://redirect-here.com/foo/bar/s.test/?d=s.test&p=%2Ffoo%2Fbar',
        ];
        yield 'invalid short URL' => [
            new Uri('/foo'),
            self::notFoundType(self::requestForRoute(RedirectAction::class)),
            new NotFoundRedirectOptions(invalidShortUrlRedirect: 'https://example.com/invalidShortUrl'),
            'https://example.com/invalidShortUrl',
        ];
        yield 'invalid short URL with path placeholder' => [
            new Uri('/foo'),
            self::notFoundType(self::requestForRoute(RedirectAction::class)),
            new NotFoundRedirectOptions(invalidShortUrlRedirect: 'https://redirect-here.com/{ORIGINAL_PATH}'),
            'https://redirect-here.com/foo',
        ];
    }

    #[Test]
    public function noResponseIsReturnedIfNoConditionsMatch(): void
    {
        $notFoundType = self::notFoundType(self::requestForRoute('foo'));
        $this->helper->expects($this->never())->method('buildRedirectResponse');

        $result = $this->resolver->resolveRedirectResponse($notFoundType, new NotFoundRedirectOptions(), new Uri());

        self::assertNull($result);
    }

    private static function notFoundType(ServerRequestInterface $req): NotFoundType
    {
        return NotFoundType::fromRequest($req, '');
    }

    private static function requestForRoute(string $routeName): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals()
            ->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(
                    new Route(
                        'foo',
                        middleware(function (): void {
                        }),
                        ['GET'],
                        $routeName,
                    ),
                ),
            )
            ->withUri(new Uri('/abc123'));
    }
}
