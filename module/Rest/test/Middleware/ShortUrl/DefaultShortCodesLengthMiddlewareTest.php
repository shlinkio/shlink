<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\ShortUrl;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Rest\Middleware\ShortUrl\DefaultShortCodesLengthMiddleware;

class DefaultShortCodesLengthMiddlewareTest extends TestCase
{
    private DefaultShortCodesLengthMiddleware $middleware;
    private MockObject & RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->middleware = new DefaultShortCodesLengthMiddleware(8);
    }

    #[Test, DataProvider('provideBodies')]
    public function defaultValueIsInjectedInBodyWhenNotProvided(array $body, int $expectedLength): void
    {
        $request = ServerRequestFactory::fromGlobals()->withParsedBody($body);
        $this->handler->expects($this->once())->method('handle')->with($this->callback(
            function (ServerRequestInterface $req) use ($expectedLength) {
                $parsedBody = (array) $req->getParsedBody();
                Assert::assertArrayHasKey(ShortUrlInputFilter::SHORT_CODE_LENGTH, $parsedBody);
                Assert::assertEquals($expectedLength, $parsedBody[ShortUrlInputFilter::SHORT_CODE_LENGTH]);

                return true;
            },
        ))->willReturn(new Response());

        $this->middleware->process($request, $this->handler);
    }

    public static function provideBodies(): iterable
    {
        yield 'value provided' => [[ShortUrlInputFilter::SHORT_CODE_LENGTH => 6], 6];
        yield 'value not provided' => [[], 8];
    }
}
