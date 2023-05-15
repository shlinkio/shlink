<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use JsonException;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function Shlinkio\Shlink\Core\toProblemDetailsType;

class MalformedBodyException extends InvalidArgumentException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    public static function forInvalidJson(JsonException $prev): self
    {
        $e = new self('Provided request does not contain a valid JSON body.', previous: $prev);

        $e->detail = $e->getMessage();
        $e->title = 'Malformed request body';
        $e->type = toProblemDetailsType('malformed-request-body');
        $e->status = StatusCodeInterface::STATUS_BAD_REQUEST;

        return $e;
    }
}
