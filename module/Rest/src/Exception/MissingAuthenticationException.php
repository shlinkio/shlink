<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function implode;
use function Shlinkio\Shlink\Core\toProblemDetailsType;
use function sprintf;

class MissingAuthenticationException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const string TITLE = 'Invalid authorization';
    public const string ERROR_CODE = 'missing-authentication';

    public static function forHeaders(array $expectedHeaders): self
    {
        $e = self::withMessage(sprintf(
            'Expected one of the following authentication headers, ["%s"], but none were provided',
            implode('", "', $expectedHeaders),
        ));
        $e->additional = ['expectedHeaders' => $expectedHeaders];

        return $e;
    }

    public static function forQueryParam(string $param): self
    {
        $e = self::withMessage(sprintf('Expected authentication to be provided in "%s" query param', $param));
        $e->additional = ['param' => $param];

        return $e;
    }

    private static function withMessage(string $message): self
    {
        $e = new self($message);

        $e->detail = $message;
        $e->title = self::TITLE;
        $e->type = toProblemDetailsType(self::ERROR_CODE);
        $e->status = StatusCodeInterface::STATUS_UNAUTHORIZED;

        return $e;
    }
}
