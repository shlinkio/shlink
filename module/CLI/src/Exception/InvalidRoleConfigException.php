<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Exception;

use InvalidArgumentException;
use Shlinkio\Shlink\Rest\ApiKey\Role;

use function sprintf;

class InvalidRoleConfigException extends InvalidArgumentException implements ExceptionInterface
{
    public static function forDomainOnlyWithDefaultDomain(): self
    {
        return new self(sprintf(
            'You cannot create an API key with the "%s" role attached to the default domain. '
            . 'The role is currently limited to non-default domains.',
            Role::DOMAIN_SPECIFIC,
        ));
    }
}
