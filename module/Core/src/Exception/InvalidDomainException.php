<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

class InvalidDomainException extends DomainException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Invalid domain';
    private const TYPE = 'INVALID_DOMAIN';

    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forDefaultDomainRedirects(): self
    {
        $e = new self('You cannot configure default domain\'s redirects this way. Use the configuration or env vars.');
        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_BAD_REQUEST;

        return $e;
    }
}
