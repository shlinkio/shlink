<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Model;

use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Rest\ApiKey\Role;

final class RoleDefinition
{
    private function __construct(private string $roleName, private array $meta)
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
            ['domain_id' => $domain->getId(), 'authority' => $domain->getAuthority()],
        );
    }

    public function roleName(): string
    {
        return $this->roleName;
    }

    public function meta(): array
    {
        return $this->meta;
    }
}
