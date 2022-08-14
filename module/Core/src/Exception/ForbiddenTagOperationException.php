<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function Shlinkio\Shlink\Core\toProblemDetailsType;

class ForbiddenTagOperationException extends DomainException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Forbidden tag operation';
    public const ERROR_CODE = 'forbidden-tag-operation';

    public static function forDeletion(): self
    {
        return self::createWithMessage('You are not allowed to delete tags');
    }

    public static function forRenaming(): self
    {
        return self::createWithMessage('You are not allowed to rename tags');
    }

    private static function createWithMessage(string $message): self
    {
        $e = new self($message);

        $e->detail = $message;
        $e->title = self::TITLE;
        $e->type = toProblemDetailsType(self::ERROR_CODE);
        $e->status = StatusCodeInterface::STATUS_FORBIDDEN;

        return $e;
    }
}
