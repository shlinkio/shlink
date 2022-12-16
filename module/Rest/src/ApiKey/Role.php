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

enum Role: string
{
    case AUTHORED_SHORT_URLS = 'AUTHORED_SHORT_URLS';
    case DOMAIN_SPECIFIC = 'DOMAIN_SPECIFIC';

    public function toFriendlyName(): string
    {
        return match ($this) {
            self::AUTHORED_SHORT_URLS => 'Author only',
            self::DOMAIN_SPECIFIC => 'Domain only',
        };
    }

    public function paramName(): string
    {
        return match ($this) {
            self::AUTHORED_SHORT_URLS => 'author-only',
            self::DOMAIN_SPECIFIC => 'domain-only',
        };
    }

    public static function toSpec(ApiKeyRole $role, ?string $context = null): Specification
    {
        return match ($role->role()) {
            self::AUTHORED_SHORT_URLS => new BelongsToApiKey($role->apiKey(), $context),
            self::DOMAIN_SPECIFIC => new BelongsToDomain(self::domainIdFromMeta($role->meta()), $context),
        };
    }

    public static function toInlinedSpec(ApiKeyRole $role): Specification
    {
        return match ($role->role()) {
            self::AUTHORED_SHORT_URLS => Spec::andX(new BelongsToApiKeyInlined($role->apiKey())),
            self::DOMAIN_SPECIFIC => Spec::andX(new BelongsToDomainInlined(self::domainIdFromMeta($role->meta()))),
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
}
