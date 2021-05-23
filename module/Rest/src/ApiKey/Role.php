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

    public static function toSpec(ApiKeyRole $role, bool $inlined, ?string $context = null): Specification
    {
        if ($role->name() === self::AUTHORED_SHORT_URLS) {
            $apiKey = $role->apiKey();
            return $inlined ? Spec::andX(new BelongsToApiKeyInlined($apiKey)) : new BelongsToApiKey($apiKey, $context);
        }

        if ($role->name() === self::DOMAIN_SPECIFIC) {
            $domainId = self::domainIdFromMeta($role->meta());
            return $inlined
                ? Spec::andX(new BelongsToDomainInlined($domainId))
                : new BelongsToDomain($domainId, $context);
        }

        return Spec::andX();
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
