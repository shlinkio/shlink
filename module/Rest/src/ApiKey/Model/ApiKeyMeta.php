<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Model;

use Cake\Chronos\Chronos;
use Ramsey\Uuid\Uuid;

final class ApiKeyMeta
{
    /**
     * @param iterable<RoleDefinition> $roleDefinitions
     */
    private function __construct(
        public readonly string $key,
        public readonly ?string $name,
        public readonly ?Chronos $expirationDate,
        public readonly iterable $roleDefinitions,
    ) {
    }

    public static function empty(): self
    {
        return self::fromParams();
    }

    /**
     * @param iterable<RoleDefinition> $roleDefinitions
     */
    public static function fromParams(
        ?string $key = null,
        ?string $name = null,
        ?Chronos $expirationDate = null,
        iterable $roleDefinitions = [],
    ): self {
        return new self(
            key: $key ?? Uuid::uuid4()->toString(),
            name: $name,
            expirationDate: $expirationDate,
            roleDefinitions: $roleDefinitions,
        );
    }

    public static function withRoles(RoleDefinition ...$roleDefinitions): self
    {
        return self::fromParams(roleDefinitions: $roleDefinitions);
    }
}
