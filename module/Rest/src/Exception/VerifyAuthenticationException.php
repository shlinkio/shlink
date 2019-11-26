<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Zend\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Zend\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function sprintf;

class VerifyAuthenticationException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    /** @var string */
    private $errorCode;
    /** @var string */
    private $publicMessage;

    public static function forInvalidApiKey(): self
    {
        $e = new self('Provided API key does not exist or is invalid.');

        $e->publicMessage = $e->getMessage();
        $e->errorCode = 'INVALID_API_KEY';
        $e->detail = $e->getMessage();
        $e->title = 'Invalid API key';
        $e->type = 'INVALID_API_KEY';
        $e->status = StatusCodeInterface::STATUS_UNAUTHORIZED;

        return $e;
    }

    /** @deprecated */
    public static function forInvalidAuthToken(): self
    {
        $e = new self(
            'Missing or invalid auth token provided. Perform a new authentication request and send provided '
            . 'token on every new request on the Authorization header'
        );

        $e->publicMessage = $e->getMessage();
        $e->errorCode = 'INVALID_AUTH_TOKEN';
        $e->detail = $e->getMessage();
        $e->title = 'Invalid auth token';
        $e->type = 'INVALID_AUTH_TOKEN';
        $e->status = StatusCodeInterface::STATUS_UNAUTHORIZED;

        return $e;
    }

    /** @deprecated */
    public static function forMissingAuthType(): self
    {
        $e = new self('You need to provide the Bearer type in the Authorization header.');

        $e->publicMessage = $e->getMessage();
        $e->errorCode = 'INVALID_AUTHORIZATION';
        $e->detail = $e->getMessage();
        $e->title = 'Invalid authorization';
        $e->type = 'INVALID_AUTHORIZATION';
        $e->status = StatusCodeInterface::STATUS_UNAUTHORIZED;

        return $e;
    }

    /** @deprecated */
    public static function forInvalidAuthType(string $providedType): self
    {
        $e = new self(sprintf('Provided authorization type %s is not supported. Use Bearer instead.', $providedType));

        $e->publicMessage = $e->getMessage();
        $e->errorCode = 'INVALID_AUTHORIZATION';
        $e->detail = $e->getMessage();
        $e->title = 'Invalid authorization';
        $e->type = 'INVALID_AUTHORIZATION';
        $e->status = StatusCodeInterface::STATUS_UNAUTHORIZED;

        return $e;
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
