<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use Throwable;
use function sprintf;

class VerifyAuthenticationException extends RuntimeException
{
    /**
     * @var string
     */
    private $errorCode;
    /**
     * @var string
     */
    private $publicMessage;

    public function __construct(
        string $errorCode,
        string $publicMessage,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->publicMessage = $publicMessage;
    }

    public static function withError(string $errorCode, string $publicMessage, Throwable $prev = null): self
    {
        return new self(
            $errorCode,
            $publicMessage,
            sprintf('Authentication verification failed with the public message "%s"', $publicMessage),
            0,
            $prev
        );
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getPublicMessage(): string
    {
        return $this->publicMessage;
    }
}
