<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\ApiKey;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\Specification;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Spec\BelongsToApiKey;
use Shlinkio\Shlink\Core\ShortUrl\Spec\BelongsToApiKeyInlined;
use Shlinkio\Shlink\Core\ShortUrl\Spec\BelongsToDomain;
use Shlinkio\Shlink\Core\ShortUrl\Spec\BelongsToDomainInlined;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Entity\ApiKeyRole;

class RoleTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideRoles
     */
    public function returnsExpectedSpec(ApiKeyRole $apiKeyRole, Specification $expected): void
    {
        self::assertEquals($expected, Role::toSpec($apiKeyRole));
    }

    public static function provideRoles(): iterable
    {
        $apiKey = ApiKey::create();

        yield 'author role' => [
            new ApiKeyRole(Role::AUTHORED_SHORT_URLS, [], $apiKey),
            new BelongsToApiKey($apiKey),
        ];
        yield 'domain role' => [
            new ApiKeyRole(Role::DOMAIN_SPECIFIC, ['domain_id' => '456'], $apiKey),
            new BelongsToDomain('456'),
        ];
    }

    /**
     * @test
     * @dataProvider provideInlinedRoles
     */
    public function returnsExpectedInlinedSpec(ApiKeyRole $apiKeyRole, Specification $expected): void
    {
        self::assertEquals($expected, Role::toInlinedSpec($apiKeyRole));
    }

    public static function provideInlinedRoles(): iterable
    {
        $apiKey = ApiKey::create();

        yield 'author role' => [
            new ApiKeyRole(Role::AUTHORED_SHORT_URLS, [], $apiKey),
            Spec::andX(new BelongsToApiKeyInlined($apiKey)),
        ];
        yield 'domain role' => [
            new ApiKeyRole(Role::DOMAIN_SPECIFIC, ['domain_id' => '123'], $apiKey),
            Spec::andX(new BelongsToDomainInlined('123')),
        ];
    }

    /**
     * @test
     * @dataProvider provideMetasWithDomainId
     */
    public function getsExpectedDomainIdFromMeta(array $meta, string $expectedDomainId): void
    {
        self::assertEquals($expectedDomainId, Role::domainIdFromMeta($meta));
    }

    public static function provideMetasWithDomainId(): iterable
    {
        yield 'empty meta' => [[], '-1'];
        yield 'meta without domain_id' => [['foo' => 'bar'], '-1'];
        yield 'meta with domain_id' => [['domain_id' => '123'], '123'];
    }

    /**
     * @test
     * @dataProvider provideMetasWithAuthority
     */
    public function getsExpectedAuthorityFromMeta(array $meta, string $expectedAuthority): void
    {
        self::assertEquals($expectedAuthority, Role::domainAuthorityFromMeta($meta));
    }

    public static function provideMetasWithAuthority(): iterable
    {
        yield 'empty meta' => [[], ''];
        yield 'meta without authority' => [['foo' => 'bar'], ''];
        yield 'meta with authority' => [['authority' => 'example.com'], 'example.com'];
    }

    /**
     * @test
     * @dataProvider provideRoleNames
     */
    public function getsExpectedRoleFriendlyName(Role $role, string $expectedFriendlyName): void
    {
        self::assertEquals($expectedFriendlyName, $role->toFriendlyName());
    }

    public static function provideRoleNames(): iterable
    {
        yield Role::AUTHORED_SHORT_URLS->value => [Role::AUTHORED_SHORT_URLS, 'Author only'];
        yield Role::DOMAIN_SPECIFIC->value => [Role::DOMAIN_SPECIFIC, 'Domain only'];
    }
}
