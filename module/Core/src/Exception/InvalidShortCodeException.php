<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Zend\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Zend\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function sprintf;

class InvalidShortCodeException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Invalid short code';
    public const TYPE = 'INVALID_SHORTCODE';

    public static function fromNotFoundShortCode(string $shortCode): self
    {
        $e = new self(sprintf('No URL found for short code "%s"', $shortCode));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_NOT_FOUND;

        return $e;
    }
}
