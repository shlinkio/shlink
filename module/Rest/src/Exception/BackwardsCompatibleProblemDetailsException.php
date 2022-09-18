<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use Shlinkio\Shlink\Core\Exception\DeleteShortUrlException;
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;
use Shlinkio\Shlink\Core\Exception\ForbiddenTagOperationException;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Exception\ValidationException;

use function explode;
use function Functional\last;

/** @deprecated */
class BackwardsCompatibleProblemDetailsException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    private function __construct(private readonly ProblemDetailsExceptionInterface $e)
    {
        parent::__construct($e->getMessage(), $e->getCode(), $e);
    }

    public static function fromProblemDetails(ProblemDetailsExceptionInterface $e): self
    {
        return new self($e);
    }

    public function getStatus(): int
    {
        return $this->e->getStatus();
    }

    public function getType(): string
    {
        return $this->remapType($this->e->getType());
    }

    public function getTitle(): string
    {
        return $this->e->getTitle();
    }

    public function getDetail(): string
    {
        return $this->e->getDetail();
    }

    public function getAdditionalData(): array
    {
        return $this->e->getAdditionalData();
    }

    public function toArray(): array
    {
        return $this->remapTypeInArray($this->e->toArray());
    }

    public function jsonSerialize(): array
    {
        return $this->remapTypeInArray($this->e->jsonSerialize());
    }

    private function remapTypeInArray(array $wrappedArray): array
    {
        if (! isset($wrappedArray['type'])) {
            return $wrappedArray;
        }

        return [...$wrappedArray, 'type' => $this->remapType($wrappedArray['type'])];
    }

    private function remapType(string $wrappedType): string
    {
        $lastSegment = last(explode('/', $wrappedType));
        return match ($lastSegment) {
            ValidationException::ERROR_CODE => 'INVALID_ARGUMENT',
            DeleteShortUrlException::ERROR_CODE => 'INVALID_SHORT_URL_DELETION',
            DomainNotFoundException::ERROR_CODE => 'DOMAIN_NOT_FOUND',
            ForbiddenTagOperationException::ERROR_CODE => 'FORBIDDEN_OPERATION',
            InvalidUrlException::ERROR_CODE => 'INVALID_URL',
            NonUniqueSlugException::ERROR_CODE => 'INVALID_SLUG',
            ShortUrlNotFoundException::ERROR_CODE => 'INVALID_SHORTCODE',
            TagConflictException::ERROR_CODE => 'TAG_CONFLICT',
            TagNotFoundException::ERROR_CODE => 'TAG_NOT_FOUND',
            MercureException::ERROR_CODE => 'MERCURE_NOT_CONFIGURED',
            MissingAuthenticationException::ERROR_CODE => 'INVALID_AUTHORIZATION',
            VerifyAuthenticationException::ERROR_CODE => 'INVALID_API_KEY',
            default => $wrappedType,
        };
    }
}
