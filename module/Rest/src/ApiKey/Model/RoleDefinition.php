<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Model;

use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Rest\ApiKey\Role;

final class RoleDefinition
{
    private function __construct(public readonly Role $role, public readonly array $meta)
    {
    }

    public static function forAuthoredShortUrls(): self
    {
        return new self(Role::AUTHORED_SHORT_URLS, []);
    }

    public static function forDomain(Domain $domain): self
    {
        return new self(
            Role::DOMAIN_SPECIFIC,
            ['domain_id' => $domain->getId(), 'authority' => $domain->authority],
        );
    }

    public static function forNoOrphanVisits(): self
    {
        return new self(Role::NO_ORPHAN_VISITS, []);
    }
}
