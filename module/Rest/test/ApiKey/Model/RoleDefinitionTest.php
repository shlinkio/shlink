<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\ApiKey\Model;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\ApiKey\Role;

class RoleDefinitionTest extends TestCase
{
    /** @test */
    public function forAuthoredShortUrlsCreatesRoleDefinitionAsExpected(): void
    {
        $definition = RoleDefinition::forAuthoredShortUrls();

        self::assertEquals(Role::AUTHORED_SHORT_URLS, $definition->roleName());
        self::assertEquals([], $definition->meta());
    }

    /** @test */
    public function forDomainCreatesRoleDefinitionAsExpected(): void
    {
        $domain = Domain::withAuthority('foo.com')->setId('123');
        $definition = RoleDefinition::forDomain($domain);

        self::assertEquals(Role::DOMAIN_SPECIFIC, $definition->roleName());
        self::assertEquals(['domain_id' => '123', 'authority' => 'foo.com'], $definition->meta());
    }
}
