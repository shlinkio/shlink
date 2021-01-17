<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

class ForbiddenTagOperationException extends DomainException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Forbidden tag operation';
    private const TYPE = 'FORBIDDEN_OPERATION';

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
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_FORBIDDEN;

        return $e;
    }
}
