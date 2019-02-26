<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Throwable;

use function sprintf;

class DeleteShortUrlException extends RuntimeException
{
    /** @var int */
    private $visitsThreshold;

    public function __construct(int $visitsThreshold, string $message = '', int $code = 0, Throwable $previous = null)
    {
        $this->visitsThreshold = $visitsThreshold;
        parent::__construct($message, $code, $previous);
    }

    public static function fromVisitsThreshold(int $threshold, string $shortCode): self
    {
        return new self($threshold, sprintf(
            'Impossible to delete short URL with short code "%s" since it has more than "%s" visits.',
            $shortCode,
            $threshold
        ));
    }

    public function getVisitsThreshold(): int
    {
        return $this->visitsThreshold;
    }
}
