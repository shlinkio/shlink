<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Model;

use Cake\Chronos\Chronos;
use Ramsey\Uuid\Uuid;

use function sprintf;
use function substr;

final readonly class ApiKeyMeta
{
    /**
     * @param iterable<RoleDefinition> $roleDefinitions
     */
    private function __construct(
        public string $key,
        public string $name,
        public bool $isNameAutoGenerated,
        public Chronos|null $expirationDate,
        public iterable $roleDefinitions,
    ) {
    }

    public static function create(): self
    {
        return self::fromParams();
    }

    /**
     * @param iterable<RoleDefinition> $roleDefinitions
     */
    public static function fromParams(
        string|null $key = null,
        string|null $name = null,
        Chronos|null $expirationDate = null,
        iterable $roleDefinitions = [],
    ): self {
        $resolvedKey = $key ?? Uuid::uuid4()->toString();
        $isNameAutoGenerated = empty($name);

        // If a name was not provided, fall back to the key
        if ($isNameAutoGenerated) {
            // If the key was auto-generated, fall back to a redacted version of the UUID, otherwise simply use the
            // plain key as fallback name
            $name = $key === null
                ? sprintf('%s-****-****-****-************', substr($resolvedKey, offset: 0, length: 8))
                : $key;
        }

        return new self(
            key: $resolvedKey,
            name: $name,
            isNameAutoGenerated: $isNameAutoGenerated,
            expirationDate: $expirationDate,
            roleDefinitions: $roleDefinitions,
        );
    }

    public static function withRoles(RoleDefinition ...$roleDefinitions): self
    {
        return self::fromParams(roleDefinitions: $roleDefinitions);
    }
}
