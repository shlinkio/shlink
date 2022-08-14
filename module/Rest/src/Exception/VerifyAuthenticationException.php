<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function Shlinkio\Shlink\Core\toProblemDetailsType;

class VerifyAuthenticationException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    public const ERROR_CODE = 'invalid-api-key';

    public static function forInvalidApiKey(): self
    {
        $e = new self('Provided API key does not exist or is invalid.');

        $e->detail = $e->getMessage();
        $e->title = 'Invalid API key';
        $e->type = toProblemDetailsType(self::ERROR_CODE);
        $e->status = StatusCodeInterface::STATUS_UNAUTHORIZED;

        return $e;
    }
}
