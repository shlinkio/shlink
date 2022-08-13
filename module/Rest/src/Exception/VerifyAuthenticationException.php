<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

class VerifyAuthenticationException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    public const TYPE = 'https://shlink.io/api/error/invalid-api-key';

    public static function forInvalidApiKey(): self
    {
        $e = new self('Provided API key does not exist or is invalid.');

        $e->detail = $e->getMessage();
        $e->title = 'Invalid API key';
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_UNAUTHORIZED;

        return $e;
    }
}
