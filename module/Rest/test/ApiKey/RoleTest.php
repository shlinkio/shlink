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
    public function returnsExpectedSpec(ApiKeyRole $apiKeyRole, bool $inlined, Specification $expected): void
    {
        self::assertEquals($expected, Role::toSpec($apiKeyRole, $inlined));
    }

    public function provideRoles(): iterable
    {
        $apiKey = new ApiKey();

        yield 'inline invalid role' => [new ApiKeyRole('invalid', [], $apiKey), true, Spec::andX()];
        yield 'not inline invalid role' => [new ApiKeyRole('invalid', [], $apiKey), false, Spec::andX()];
        yield 'inline author role' => [
            new ApiKeyRole(Role::AUTHORED_SHORT_URLS, [], $apiKey),
            true,
            new BelongsToApiKeyInlined($apiKey),
        ];
        yield 'not inline author role' => [
            new ApiKeyRole(Role::AUTHORED_SHORT_URLS, [], $apiKey),
            false,
            new BelongsToApiKey($apiKey),
        ];
        yield 'inline domain role' => [
            new ApiKeyRole(Role::DOMAIN_SPECIFIC, ['domain_id' => '123'], $apiKey),
            true,
            new BelongsToDomainInlined('123'),
        ];
        yield 'not inline domain role' => [
            new ApiKeyRole(Role::DOMAIN_SPECIFIC, ['domain_id' => '456'], $apiKey),
            false,
            new BelongsToDomain('456'),
        ];
    }

    /**
     * @test
     * @dataProvider provideMetas
     */
    public function getsExpectedDomainIdFromMeta(array $meta, string $expectedDomainId): void
    {
        self::assertEquals($expectedDomainId, Role::domainIdFromMeta($meta));
    }

    public function provideMetas(): iterable
    {
        yield 'empty meta' => [[], '-1'];
        yield 'meta without domain_id' => [['foo' => 'bar'], '-1'];
        yield 'meta with domain_id' => [['domain_id' => '123'], '123'];
    }
}
