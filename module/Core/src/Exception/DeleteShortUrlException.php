<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;
use Zend\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Zend\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function sprintf;

class DeleteShortUrlException extends DomainException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Cannot delete short URL';
    private const TYPE = 'INVALID_SHORTCODE_DELETION'; // FIXME Should be INVALID_SHORT_URL_DELETION

    /** @var int */
    private $visitsThreshold;

    public function __construct(int $visitsThreshold, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $this->visitsThreshold = $visitsThreshold;
        parent::__construct($message, $code, $previous);
    }

    public static function fromVisitsThreshold(int $threshold, string $shortCode): self
    {
        $e = new self($threshold, sprintf(
            'Impossible to delete short URL with short code "%s" since it has more than "%s" visits.',
            $shortCode,
            $threshold
        ));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY;
        $e->additional = ['threshold' => $threshold];

        return $e;
    }

    public function getVisitsThreshold(): int
    {
        return $this->visitsThreshold;
    }
}
