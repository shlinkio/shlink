<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Core\ShortUrl\Spec\BelongsToApiKey;
use Shlinkio\Shlink\Core\ShortUrl\Spec\BelongsToApiKeyInlined;
use Shlinkio\Shlink\Core\ShortUrl\Spec\BelongsToDomain;
use Shlinkio\Shlink\Core\ShortUrl\Spec\BelongsToDomainInlined;
use Shlinkio\Shlink\Rest\Entity\ApiKeyRole;

class Role
{
    public const AUTHORED_SHORT_URLS = 'AUTHORED_SHORT_URLS';
    public const DOMAIN_SPECIFIC = 'DOMAIN_SPECIFIC';
    private const ROLE_FRIENDLY_NAMES = [
        self::AUTHORED_SHORT_URLS => 'Author only',
        self::DOMAIN_SPECIFIC => 'Domain only',
    ];

    public static function toSpec(ApiKeyRole $role, ?string $context = null): Specification
    {
        return match ($role->name()) {
            self::AUTHORED_SHORT_URLS => new BelongsToApiKey($role->apiKey(), $context),
            self::DOMAIN_SPECIFIC => new BelongsToDomain(self::domainIdFromMeta($role->meta()), $context),
            default => Spec::andX(),
        };
    }

    public static function toInlinedSpec(ApiKeyRole $role): Specification
    {
        return match ($role->name()) {
            self::AUTHORED_SHORT_URLS => Spec::andX(new BelongsToApiKeyInlined($role->apiKey())),
            self::DOMAIN_SPECIFIC => Spec::andX(new BelongsToDomainInlined(self::domainIdFromMeta($role->meta()))),
            default => Spec::andX(),
        };
    }

    public static function domainIdFromMeta(array $meta): string
    {
        return $meta['domain_id'] ?? '-1';
    }

    public static function domainAuthorityFromMeta(array $meta): string
    {
        return $meta['authority'] ?? '';
    }

    public static function toFriendlyName(string $roleName): string
    {
        return self::ROLE_FRIENDLY_NAMES[$roleName] ?? '';
    }
}
