<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

class MercureException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Mercure integration not configured';
    private const TYPE = 'MERCURE_NOT_CONFIGURED';

    public static function mercureNotConfigured(): self
    {
        $e = new self('This Shlink instance is not integrated with a mercure hub.');

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_NOT_IMPLEMENTED;

        return $e;
    }
}
