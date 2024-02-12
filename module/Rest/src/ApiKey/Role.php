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

use function sprintf;

enum Role: string
{
    case AUTHORED_SHORT_URLS = 'AUTHORED_SHORT_URLS';
    case DOMAIN_SPECIFIC = 'DOMAIN_SPECIFIC';
    case NO_ORPHAN_VISITS = 'NO_ORPHAN_VISITS';

    public function toFriendlyName(array $meta): string
    {
        return match ($this) {
            self::AUTHORED_SHORT_URLS => 'Author only',
            self::DOMAIN_SPECIFIC => sprintf('Domain only: %s', Role::domainAuthorityFromMeta($meta)),
            self::NO_ORPHAN_VISITS => 'No orphan visits',
        };
    }

    public function paramName(): string
    {
        return match ($this) {
            self::AUTHORED_SHORT_URLS => 'author-only',
            self::DOMAIN_SPECIFIC => 'domain-only',
            self::NO_ORPHAN_VISITS => 'no-orphan-visits',
        };
    }

    public static function toSpec(ApiKeyRole $role, ?string $context = null): Specification
    {
        return match ($role->role) {
            self::AUTHORED_SHORT_URLS => new BelongsToApiKey($role->apiKey, $context),
            self::DOMAIN_SPECIFIC => new BelongsToDomain(self::domainIdFromMeta($role->meta()), $context),
            default => Spec::andX(),
        };
    }

    public static function toInlinedSpec(ApiKeyRole $role): Specification
    {
        return match ($role->role) {
            self::AUTHORED_SHORT_URLS => Spec::andX(new BelongsToApiKeyInlined($role->apiKey)),
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
}
