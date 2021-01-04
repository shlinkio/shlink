<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Entity;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\Specification;
use Ramsey\Uuid\Uuid;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Rest\ApiKey\Role;

class ApiKey extends AbstractEntity
{
    private string $key;
    private ?Chronos $expirationDate = null;
    private bool $enabled;
    /** @var Collection|ApiKeyRole[] */
    private Collection $roles;

    public function __construct(?Chronos $expirationDate = null)
    {
        $this->key = Uuid::uuid4()->toString();
        $this->expirationDate = $expirationDate;
        $this->enabled = true;
        $this->roles = new ArrayCollection();
    }

    public function getExpirationDate(): ?Chronos
    {
        return $this->expirationDate;
    }

    public function isExpired(): bool
    {
        return $this->expirationDate !== null && $this->expirationDate->lt(Chronos::now());
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function disable(): self
    {
        $this->enabled = false;
        return $this;
    }

    /**
     * Tells if this api key is enabled and not expired
     */
    public function isValid(): bool
    {
        return $this->isEnabled() && ! $this->isExpired();
    }

    public function __toString(): string
    {
        return $this->key;
    }

    public function toString(): string
    {
        return $this->key;
    }

    public function spec(bool $inlined = false): Specification
    {
        $specs = $this->roles->map(fn (ApiKeyRole $role) => Role::toSpec($role, $inlined));
        return Spec::andX(...$specs);
    }

    public function isAdmin(): bool
    {
        return $this->roles->isEmpty();
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->exists(fn ($key, ApiKeyRole $role) => $role->name() === $roleName);
    }

    public function getRoleMeta(string $roleName): array
    {
        /** @var ApiKeyRole|false $role */
        $role = $this->roles->filter(fn (ApiKeyRole $role) => $role->name() === $roleName)->first();
        return ! $role ? [] : $role->meta();
    }
}
