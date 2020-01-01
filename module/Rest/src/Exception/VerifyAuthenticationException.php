<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

class VerifyAuthenticationException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    public static function forInvalidApiKey(): self
    {
        $e = new self('Provided API key does not exist or is invalid.');

        $e->detail = $e->getMessage();
        $e->title = 'Invalid API key';
        $e->type = 'INVALID_API_KEY';
        $e->status = StatusCodeInterface::STATUS_UNAUTHORIZED;

        return $e;
    }
}
