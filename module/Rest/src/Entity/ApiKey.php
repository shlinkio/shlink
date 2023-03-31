<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Entity;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\Specification;
use Ramsey\Uuid\Uuid;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\ApiKey\Role;

class ApiKey extends AbstractEntity
{
    private string $key;
    private ?Chronos $expirationDate = null;
    private bool $enabled;
    /** @var Collection<string, ApiKeyRole> */
    private Collection $roles;
    private ?string $name = null;

    /**
     * @throws Exception
     */
    private function __construct(?string $key = null)
    {
        $this->key = $key ?? Uuid::uuid4()->toString();
        $this->enabled = true;
        $this->roles = new ArrayCollection();
    }

    public static function create(): ApiKey
    {
        return new self();
    }

    public static function fromMeta(ApiKeyMeta $meta): self
    {
        $apiKey = self::create();
        $apiKey->name = $meta->name;
        $apiKey->expirationDate = $meta->expirationDate;

        foreach ($meta->roleDefinitions as $roleDefinition) {
            $apiKey->registerRole($roleDefinition);
        }

        return $apiKey;
    }

    public static function fromKey(string $key): self
    {
        return new self($key);
    }

    public function getExpirationDate(): ?Chronos
    {
        return $this->expirationDate;
    }

    public function isExpired(): bool
    {
        return $this->expirationDate !== null && $this->expirationDate->lt(Chronos::now());
    }

    public function name(): ?string
    {
        return $this->name;
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

    public function spec(?string $context = null): Specification
    {
        $specs = $this->roles->map(fn (ApiKeyRole $role) => Role::toSpec($role, $context))->getValues();
        return Spec::andX(...$specs);
    }

    public function inlinedSpec(): Specification
    {
        $specs = $this->roles->map(fn (ApiKeyRole $role) => Role::toInlinedSpec($role))->getValues();
        return Spec::andX(...$specs);
    }

    /**
     * @return ($apiKey is null ? true : boolean)
     */
    public static function isAdmin(?ApiKey $apiKey): bool
    {
        return $apiKey === null || $apiKey->roles->isEmpty();
    }

    public function hasRole(Role $role): bool
    {
        return $this->roles->containsKey($role->value);
    }

    public function getRoleMeta(Role $role): array
    {
        /** @var ApiKeyRole|null $apiKeyRole */
        $apiKeyRole = $this->roles->get($role->value);
        return $apiKeyRole?->meta() ?? [];
    }

    /**
     * @template T
     * @param callable(Role $role, array $meta): T $fun
     * @return T[]
     */
    public function mapRoles(callable $fun): array
    {
        return $this->roles->map(fn (ApiKeyRole $role) => $fun($role->role(), $role->meta()))->getValues();
    }

    public function registerRole(RoleDefinition $roleDefinition): void
    {
        $role = $roleDefinition->role;
        $meta = $roleDefinition->meta;

        if ($this->hasRole($role)) {
            $this->roles->get($role->value)?->updateMeta($meta);
        } else {
            $this->roles->set($role->value, new ApiKeyRole($role, $meta, $this));
        }
    }
}
