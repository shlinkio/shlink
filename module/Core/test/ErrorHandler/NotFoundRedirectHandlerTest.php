<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ErrorHandler;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectResolverInterface;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\ErrorHandler\NotFoundRedirectHandler;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;

class NotFoundRedirectHandlerTest extends TestCase
{
    private NotFoundRedirectHandler $middleware;
    private NotFoundRedirectOptions $redirectOptions;
    private MockObject & NotFoundRedirectResolverInterface $resolver;
    private MockObject & DomainServiceInterface $domainService;
    private MockObject & RequestHandlerInterface $next;
    private ServerRequestInterface $req;

    protected function setUp(): void
    {
        $this->redirectOptions = new NotFoundRedirectOptions();
        $this->resolver = $this->createMock(NotFoundRedirectResolverInterface::class);
        $this->domainService = $this->createMock(DomainServiceInterface::class);

        $this->middleware = new NotFoundRedirectHandler($this->redirectOptions, $this->resolver, $this->domainService);

        $this->next = $this->createMock(RequestHandlerInterface::class);
        $this->req = ServerRequestFactory::fromGlobals()->withAttribute(
            NotFoundType::class,
            $this->createMock(NotFoundType::class),
        );
    }

    /**
     * @test
     * @dataProvider provideNonRedirectScenarios
     */
    public function nextIsCalledWhenNoRedirectIsResolved(callable $setUp): void
    {
        $expectedResp = new Response();

        $setUp($this->domainService, $this->resolver);
        $this->next->expects($this->once())->method('handle')->with($this->req)->willReturn($expectedResp);

        $result = $this->middleware->process($this->req, $this->next);

        self::assertSame($expectedResp, $result);
    }

    public static function provideNonRedirectScenarios(): iterable
    {
        yield 'no domain' => [function (
            MockObject&DomainServiceInterface $domainService,
            MockObject&NotFoundRedirectResolverInterface $resolver,
        ): void {
            $domainService->expects(self::once())->method('findByAuthority')->withAnyParameters()->willReturn(
                null,
            );
            $resolver->expects(self::once())->method('resolveRedirectResponse')->with(
                self::isInstanceOf(NotFoundType::class),
                self::isInstanceOf(NotFoundRedirectOptions::class),
                self::isInstanceOf(UriInterface::class),
            )->willReturn(null);
        }];
        yield 'non-redirecting domain' => [function (
            MockObject&DomainServiceInterface $domainService,
            MockObject&NotFoundRedirectResolverInterface $resolver,
        ): void {
            $domainService->expects(self::once())->method('findByAuthority')->withAnyParameters()->willReturn(
                Domain::withAuthority(''),
            );
            $callCount = 0;
            $resolver->expects(self::exactly(2))->method('resolveRedirectResponse')->willReturnCallback(
                function (mixed $arg1, mixed $arg2, mixed $arg3) use (&$callCount) {
                    Assert::assertInstanceOf(NotFoundType::class, $arg1);
                    Assert::assertInstanceOf($callCount === 0 ? Domain::class : NotFoundRedirectOptions::class, $arg2);
                    Assert::assertInstanceOf(UriInterface::class, $arg3);

                    $callCount++;
                    return null;
                },
            );
        }];
    }

    /** @test */
    public function globalRedirectIsUsedIfDomainRedirectIsNotFound(): void
    {
        $expectedResp = new Response();

        $this->domainService->expects($this->once())->method('findByAuthority')->withAnyParameters()->willReturn(null);
        $this->resolver->expects($this->once())->method('resolveRedirectResponse')->with(
            $this->isInstanceOf(NotFoundType::class),
            $this->redirectOptions,
            $this->isInstanceOf(UriInterface::class),
        )->willReturn($expectedResp);
        $this->next->expects($this->never())->method('handle');

        $result = $this->middleware->process($this->req, $this->next);

        self::assertSame($expectedResp, $result);
    }

    /** @test */
    public function domainRedirectIsUsedIfFound(): void
    {
        $expectedResp = new Response();
        $domain = Domain::withAuthority('');

        $this->domainService->expects($this->once())->method('findByAuthority')->withAnyParameters()->willReturn(
            $domain,
        );
        $this->resolver->expects($this->once())->method('resolveRedirectResponse')->with(
            $this->isInstanceOf(NotFoundType::class),
            $domain,
            $this->isInstanceOf(UriInterface::class),
        )->willReturn($expectedResp);
        $this->next->expects($this->never())->method('handle');

        $result = $this->middleware->process($this->req, $this->next);

        self::assertSame($expectedResp, $result);
    }
}
