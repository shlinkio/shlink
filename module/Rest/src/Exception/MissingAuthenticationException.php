<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Zend\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Zend\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function implode;
use function sprintf;

class MissingAuthenticationException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Invalid authorization';
    private const TYPE = 'INVALID_AUTHORIZATION';

    public static function fromExpectedTypes(array $expectedTypes): self
    {
        $e = new self(sprintf(
            'Expected one of the following authentication headers, but none were provided, ["%s"]',
            implode('", "', $expectedTypes)
        ));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_UNAUTHORIZED;
        $e->additional = ['expectedTypes' => $expectedTypes];

        return $e;
    }
}
