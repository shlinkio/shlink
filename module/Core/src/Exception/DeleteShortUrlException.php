<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function sprintf;

class DeleteShortUrlException extends DomainException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Cannot delete short URL';
    private const TYPE = 'INVALID_SHORTCODE_DELETION'; // FIXME Should be INVALID_SHORT_URL_DELETION

    public static function fromVisitsThreshold(int $threshold, string $shortCode): self
    {
        $e = new self(sprintf(
            'Impossible to delete short URL with short code "%s" since it has more than "%s" visits.',
            $shortCode,
            $threshold,
        ));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY;
        $e->additional = [
            'shortCode' => $shortCode,
            'threshold' => $threshold,
        ];

        return $e;
    }

    public function getVisitsThreshold(): int
    {
        return $this->additional['threshold'];
    }
}
