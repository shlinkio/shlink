<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Exception;

use Exception;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\DeleteShortUrlException;
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;
use Shlinkio\Shlink\Core\Exception\ForbiddenTagOperationException;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Rest\Exception\BackwardsCompatibleProblemDetailsException;
use Shlinkio\Shlink\Rest\Exception\MercureException;
use Shlinkio\Shlink\Rest\Exception\MissingAuthenticationException;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;

class BackwardsCompatibleProblemDetailsExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideTypes
     */
    public function typeIsRemappedOnWrappedException(
        string $wrappedType,
        string $expectedType,
        bool $expectSameType = false,
    ): void {
        $original = new class ($wrappedType) extends Exception implements ProblemDetailsExceptionInterface {
            public function __construct(private readonly string $type)
            {
                parent::__construct('');
            }

            public function getStatus(): int
            {
                return 123;
            }

            public function getType(): string
            {
                return $this->type;
            }

            public function getTitle(): string
            {
                return 'title';
            }

            public function getDetail(): string
            {
                return 'detail';
            }

            public function getAdditionalData(): array
            {
                return [];
            }

            public function toArray(): array
            {
                return ['type' => $this->type];
            }

            public function jsonSerialize(): array
            {
                return ['type' => $this->type];
            }
        };
        $e = BackwardsCompatibleProblemDetailsException::fromProblemDetails($original);

        self::assertEquals($e->getType(), $expectedType);
        self::assertEquals($e->toArray(), ['type' => $expectedType]);
        self::assertEquals($e->jsonSerialize(), ['type' => $expectedType]);

        self::assertEquals($original->getTitle(), $e->getTitle());
        self::assertEquals($original->getDetail(), $e->getDetail());
        self::assertEquals($original->getAdditionalData(), $e->getAdditionalData());

        if ($expectSameType) {
            self::assertEquals($original->getType(), $e->getType());
            self::assertEquals($original->toArray(), $e->toArray());
            self::assertEquals($original->jsonSerialize(), $e->jsonSerialize());
        } else {
            self::assertNotEquals($original->getType(), $e->getType());
            self::assertNotEquals($original->toArray(), $e->toArray());
            self::assertNotEquals($original->jsonSerialize(), $e->jsonSerialize());
        }
    }

    public function provideTypes(): iterable
    {
        yield ['foo', 'foo', true];
        yield ['bar', 'bar', true];
        yield [ValidationException::TYPE, 'INVALID_ARGUMENT'];
        yield [DeleteShortUrlException::TYPE, 'INVALID_SHORT_URL_DELETION'];
        yield [DomainNotFoundException::TYPE, 'DOMAIN_NOT_FOUND'];
        yield [ForbiddenTagOperationException::TYPE, 'FORBIDDEN_OPERATION'];
        yield [InvalidUrlException::TYPE, 'INVALID_URL'];
        yield [NonUniqueSlugException::TYPE, 'INVALID_SLUG'];
        yield [ShortUrlNotFoundException::TYPE, 'INVALID_SHORTCODE'];
        yield [TagConflictException::TYPE, 'TAG_CONFLICT'];
        yield [TagNotFoundException::TYPE, 'TAG_NOT_FOUND'];
        yield [MercureException::TYPE, 'MERCURE_NOT_CONFIGURED'];
        yield [MissingAuthenticationException::TYPE, 'INVALID_AUTHORIZATION'];
        yield [VerifyAuthenticationException::TYPE, 'INVALID_API_KEY'];
    }
}
