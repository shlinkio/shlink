<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Core\ShortUrl\Spec\BelongsToApiKey;
use Shlinkio\Shlink\Core\ShortUrl\Spec\BelongsToDomain;
use Shlinkio\Shlink\Rest\Entity\ApiKeyRole;

class Role
{
    public const AUTHORED_SHORT_URLS = 'AUTHORED_SHORT_URLS';
    public const DOMAIN_SPECIFIC = 'DOMAIN_SPECIFIC';

    public static function toSpec(ApiKeyRole $role): Specification
    {
        if ($role->name() === self::AUTHORED_SHORT_URLS) {
            return new BelongsToApiKey($role->apiKey());
        }

        if ($role->name() === self::DOMAIN_SPECIFIC) {
            return new BelongsToDomain($role->meta()['domain_id'] ?? -1);
        }

        return Spec::andX();
    }
}
