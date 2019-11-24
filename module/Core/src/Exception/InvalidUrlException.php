<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;
use Zend\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Zend\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function sprintf;

class InvalidUrlException extends DomainException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Invalid URL';
    public const TYPE = 'INVALID_URL';

    public static function fromUrl(string $url, ?Throwable $previous = null): self
    {
        $status = StatusCodeInterface::STATUS_BAD_REQUEST;
        $e = new self(sprintf('Provided URL %s is invalid. Try with a different one.', $url), $status, $previous);

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = $status;

        return $e;
    }
}
