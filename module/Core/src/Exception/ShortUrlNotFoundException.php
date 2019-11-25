<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Zend\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Zend\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function sprintf;

class ShortUrlNotFoundException extends DomainException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Short URL not found';
    public const TYPE = 'INVALID_SHORTCODE';

    public static function fromNotFoundShortCode(string $shortCode, ?string $domain = null): self
    {
        $suffix = $domain === null ? '' : sprintf(' for domain "%s"', $domain);
        $e = new self(sprintf('No URL found with short code "%s"%s', $shortCode, $suffix));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_NOT_FOUND;

        return $e;
    }
}
