<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;

use function Shlinkio\Shlink\Core\toProblemDetailsType;
use function sprintf;

class DeleteShortUrlException extends DomainException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Cannot delete short URL';
    public const ERROR_CODE = 'invalid-short-url-deletion';

    public static function fromVisitsThreshold(int $threshold, ShortUrlIdentifier $identifier): self
    {
        $shortCode = $identifier->shortCode;
        $domain = $identifier->domain;
        $suffix = $domain === null ? '' : sprintf(' for domain "%s"', $domain);
        $e = new self(sprintf(
            'Impossible to delete short URL with short code "%s"%s, since it has more than "%s" visits.',
            $shortCode,
            $suffix,
            $threshold,
        ));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = toProblemDetailsType(self::ERROR_CODE);
        $e->status = StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY;
        $e->additional = [
            'shortCode' => $shortCode,
            'threshold' => $threshold,
        ];

        if ($domain !== null) {
            $e->additional['domain'] = $domain;
        }

        return $e;
    }

    public function getVisitsThreshold(): int
    {
        return $this->additional['threshold'];
    }
}
