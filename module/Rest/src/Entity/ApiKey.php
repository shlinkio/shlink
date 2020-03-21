<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Entity;

use Cake\Chronos\Chronos;
use Ramsey\Uuid\Uuid;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;

class ApiKey extends AbstractEntity
{
    private string $key;
    private ?Chronos $expirationDate = null;
    private bool $enabled;

    public function __construct(?Chronos $expirationDate = null)
    {
        $this->key = Uuid::uuid4()->toString();
        $this->expirationDate = $expirationDate;
        $this->enabled = true;
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
}
