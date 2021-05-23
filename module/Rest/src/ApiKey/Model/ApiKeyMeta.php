<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Model;

use Cake\Chronos\Chronos;

final class ApiKeyMeta
{
    private function __construct(
        private ?string $name,
        private ?Chronos $expirationDate,
        /** @var RoleDefinition[] */
        private array $roleDefinitions,
    ) {
    }

    public static function withName(string $name): self
    {
        return new self($name, null, []);
    }

    public static function withExpirationDate(Chronos $expirationDate): self
    {
        return new self(null, $expirationDate, []);
    }

    public static function withNameAndExpirationDate(string $name, Chronos $expirationDate): self
    {
        return new self($name, $expirationDate, []);
    }

    public static function withRoles(RoleDefinition ...$roleDefinitions): self
    {
        return new self(null, null, $roleDefinitions);
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function expirationDate(): ?Chronos
    {
        return $this->expirationDate;
    }

    /**
     * @return RoleDefinition[]
     */
    public function roleDefinitions(): array
    {
        return $this->roleDefinitions;
    }
}
