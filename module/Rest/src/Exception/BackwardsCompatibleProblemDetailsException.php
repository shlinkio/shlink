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
        return match ($wrappedType) {
            ValidationException::TYPE => 'INVALID_ARGUMENT',
            DeleteShortUrlException::TYPE => 'INVALID_SHORT_URL_DELETION',
            DomainNotFoundException::TYPE => 'DOMAIN_NOT_FOUND',
            ForbiddenTagOperationException::TYPE => 'FORBIDDEN_OPERATION',
            InvalidUrlException::TYPE => 'INVALID_URL',
            NonUniqueSlugException::TYPE => 'INVALID_SLUG',
            ShortUrlNotFoundException::TYPE => 'INVALID_SHORTCODE',
            TagConflictException::TYPE => 'TAG_CONFLICT',
            TagNotFoundException::TYPE => 'TAG_NOT_FOUND',
            MercureException::TYPE => 'MERCURE_NOT_CONFIGURED',
            MissingAuthenticationException::TYPE => 'INVALID_AUTHORIZATION',
            VerifyAuthenticationException::TYPE => 'INVALID_API_KEY',
            default => $wrappedType,
        };
    }
}
