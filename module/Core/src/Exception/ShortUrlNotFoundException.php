<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;

use function Shlinkio\Shlink\Core\toProblemDetailsType;
use function sprintf;

class ShortUrlNotFoundException extends DomainException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Short URL not found';
    public const ERROR_CODE = 'short-url-not-found';

    public static function fromNotFound(ShortUrlIdentifier $identifier): self
    {
        $shortCode = $identifier->shortCode;
        $domain = $identifier->domain;
        $suffix = $domain === null ? '' : sprintf(' for domain "%s"', $domain);
        $e = new self(sprintf('No URL found with short code "%s"%s', $shortCode, $suffix));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = toProblemDetailsType(self::ERROR_CODE);
        $e->status = StatusCodeInterface::STATUS_NOT_FOUND;
        $e->additional = ['shortCode' => $shortCode];

        if ($domain !== null) {
            $e->additional['domain'] = $domain;
        }

        return $e;
    }
}
