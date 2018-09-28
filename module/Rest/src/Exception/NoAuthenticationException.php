<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use function implode;
use function sprintf;

class NoAuthenticationException extends RuntimeException
{
    public static function fromExpectedTypes(array $expectedTypes): self
    {
        return new self(sprintf(
            'None of the valid authentication mechanisms where provided. Expected one of ["%s"]',
            implode('", "', $expectedTypes)
        ));
    }
}
