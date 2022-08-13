<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\ErrorHandler;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Rest\Exception\BackwardsCompatibleProblemDetailsException;
use Shlinkio\Shlink\Rest\Middleware\ErrorHandler\BackwardsCompatibleProblemDetailsHandler;
use Throwable;

class BackwardsCompatibleProblemDetailsHandlerTest extends TestCase
{
    use ProphecyTrait;

    private BackwardsCompatibleProblemDetailsHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new BackwardsCompatibleProblemDetailsHandler();
    }

    /**
     * @test
     * @dataProvider provideExceptions
     */
    public function expectedExceptionIsThrownBasedOnTheRequestVersion(
        ServerRequestInterface $request,
        Throwable $thrownException,
        string $expectedException,
    ): void {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handle = $handler->handle($request)->willThrow($thrownException);

        $this->expectException($expectedException);
        $handle->shouldBeCalledOnce();

        $this->handler->process($request, $handler->reveal());
    }

    public function provideExceptions(): iterable
    {
        $baseRequest = ServerRequestFactory::fromGlobals();

        yield 'no version' => [
            $baseRequest,
            ValidationException::fromArray([]),
            BackwardsCompatibleProblemDetailsException::class,
        ];
        yield 'version 1' => [
            $baseRequest->withAttribute('version', '1'),
            ValidationException::fromArray([]),
            BackwardsCompatibleProblemDetailsException::class,
        ];
        yield 'version 2' => [
            $baseRequest->withAttribute('version', '2'),
            ValidationException::fromArray([]),
            BackwardsCompatibleProblemDetailsException::class,
        ];
        yield 'version 3' => [
            $baseRequest->withAttribute('version', '3'),
            ValidationException::fromArray([]),
            ValidationException::class,
        ];
        yield 'version 4' => [
            $baseRequest->withAttribute('version', '3'),
            ValidationException::fromArray([]),
            ValidationException::class,
        ];
    }
}
