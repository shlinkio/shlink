<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ObjectMapper;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use CuyZ\Valinor\Mapper\Tree\Message\Messages;
use CuyZ\Valinor\Mapper\Tree\Message\NodeMessage;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\ObjectMapper\MapValidationMiddleware;
use Throwable;

class MapValidationMiddlewareTest extends TestCase
{
    private MapValidationMiddleware $middleware;

    public function setUp(): void
    {
        $this->middleware = new MapValidationMiddleware();
    }

    #[Test]
    public function unknownErrorsAreThrownVerbatim(): void
    {
        $e = new RuntimeException('error');

        $this->expectExceptionObject($e);

        $this->middleware->process(ServerRequestFactory::fromGlobals(), $this->createHandlerWithError($e));
    }

    #[Test]
    public function convertsMappingErrorsToValidationExceptions(): void
    {
        $e = new class extends RuntimeException implements MappingError {
            public function messages(): Messages
            {
                // @phpstan-ignore-next-line
                return new Messages(
                    // @phpstan-ignore-next-line
                    new NodeMessage(
                        message: MessageBuilder::newError('error')->build(),
                        body: 'The error body',
                        name: 'the-error',
                        path: 'path',
                        type: 'type',
                        expectedSignature: 'expectedSignature',
                        sourceValue: 'sourceValue',
                    ),
                    // @phpstan-ignore-next-line
                    new NodeMessage(
                        message: MessageBuilder::newError('error')->build(),
                        body: 'The second error body',
                        name: 'the-second-error',
                        path: 'path',
                        type: 'type',
                        expectedSignature: 'expectedSignature',
                        sourceValue: 'sourceValue',
                    ),
                );
            }

            public function type(): string
            {
                return '';
            }

            public function source(): mixed
            {
                return '';
            }
        };

        try {
            $this->middleware->process(ServerRequestFactory::fromGlobals(), $this->createHandlerWithError($e));
            $this->fail('A ValidationException was not thrown');
        } catch (ValidationException $e) {
            self::assertEquals([
                'the-error' => 'The error body',
                'the-second-error' => 'The second error body',
            ], $e->invalidElements);
        }
    }

    private function createHandlerWithError(Throwable $e): RequestHandlerInterface
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willThrowException($e);

        return $handler;
    }
}
