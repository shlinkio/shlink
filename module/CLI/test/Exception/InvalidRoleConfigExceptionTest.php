<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Exception\InvalidRoleConfigException;
use Shlinkio\Shlink\Rest\ApiKey\Role;

use function sprintf;

class InvalidRoleConfigExceptionTest extends TestCase
{
    /** @test */
    public function forDomainOnlyWithDefaultDomainGeneratesExpectedException(): void
    {
        $e = InvalidRoleConfigException::forDomainOnlyWithDefaultDomain();

        self::assertEquals(sprintf(
            'You cannot create an API key with the "%s" role attached to the default domain. '
            . 'The role is currently limited to non-default domains.',
            Role::DOMAIN_SPECIFIC,
        ), $e->getMessage());
    }
}
