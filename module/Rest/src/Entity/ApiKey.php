<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Entity;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\ApiKey\Role;

use function hash;

class ApiKey extends AbstractEntity
{
    /**
     * @param Collection<string, ApiKeyRole> $roles
     */
    private function __construct(
        public readonly string $key,
        public readonly string|null $name = null,
        public readonly Chronos|null $expirationDate = null,
        private bool $enabled = true,
        private Collection $roles = new ArrayCollection(),
    ) {
    }

    /**
     * @throws Exception
     */
    public static function create(): ApiKey
    {
        return self::fromMeta(ApiKeyMeta::empty());
    }

    /**
     * @throws Exception
     */
    public static function fromMeta(ApiKeyMeta $meta): self
    {
        $apiKey = new self(self::hashKey($meta->key), $meta->name, $meta->expirationDate);
        foreach ($meta->roleDefinitions as $roleDefinition) {
            $apiKey->registerRole($roleDefinition);
        }

        return $apiKey;
    }

    /**
     * Generates a hash for provided key, in the way Shlink expects API keys to be hashed
     */
    public static function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }

    public function isExpired(): bool
    {
        return $this->expirationDate !== null && $this->expirationDate->lessThan(Chronos::now());
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

    public function spec(string|null $context = null): Specification
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
    public static function isAdmin(ApiKey|null $apiKey): bool
    {
        return $apiKey === null || $apiKey->roles->isEmpty();
    }

    /**
     * Tells if provided API key has any of the roles restricting at the short URL level
     */
    public static function isShortUrlRestricted(ApiKey|null $apiKey): bool
    {
        if ($apiKey === null) {
            return false;
        }

        return (
            $apiKey->roles->containsKey(Role::AUTHORED_SHORT_URLS->value)
            || $apiKey->roles->containsKey(Role::DOMAIN_SPECIFIC->value)
        );
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
        return $this->roles->map(fn (ApiKeyRole $role) => $fun($role->role, $role->meta()))->getValues();
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
