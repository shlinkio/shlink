<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function Shlinkio\Shlink\Core\toProblemDetailsType;

class MercureException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Mercure integration not configured';
    public const ERROR_CODE = 'mercure-not-configured';

    public static function mercureNotConfigured(): self
    {
        $e = new self('This Shlink instance is not integrated with a mercure hub.');

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = toProblemDetailsType(self::ERROR_CODE);
        $e->status = StatusCodeInterface::STATUS_NOT_IMPLEMENTED;

        return $e;
    }
}
