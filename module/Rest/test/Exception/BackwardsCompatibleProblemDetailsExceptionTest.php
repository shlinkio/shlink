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

    public static function provideTypes(): iterable
    {
        yield ['foo', 'foo', true];
        yield ['bar', 'bar', true];
        yield [ValidationException::ERROR_CODE, 'INVALID_ARGUMENT'];
        yield [DeleteShortUrlException::ERROR_CODE, 'INVALID_SHORT_URL_DELETION'];
        yield [DomainNotFoundException::ERROR_CODE, 'DOMAIN_NOT_FOUND'];
        yield [ForbiddenTagOperationException::ERROR_CODE, 'FORBIDDEN_OPERATION'];
        yield [InvalidUrlException::ERROR_CODE, 'INVALID_URL'];
        yield [NonUniqueSlugException::ERROR_CODE, 'INVALID_SLUG'];
        yield [ShortUrlNotFoundException::ERROR_CODE, 'INVALID_SHORTCODE'];
        yield [TagConflictException::ERROR_CODE, 'TAG_CONFLICT'];
        yield [TagNotFoundException::ERROR_CODE, 'TAG_NOT_FOUND'];
        yield [MercureException::ERROR_CODE, 'MERCURE_NOT_CONFIGURED'];
        yield [MissingAuthenticationException::ERROR_CODE, 'INVALID_AUTHORIZATION'];
        yield [VerifyAuthenticationException::ERROR_CODE, 'INVALID_API_KEY'];
    }
}
