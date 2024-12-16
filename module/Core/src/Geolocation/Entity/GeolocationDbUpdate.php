<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Geolocation\Entity;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Exception\RuntimeException;

use function stat;

class GeolocationDbUpdate extends AbstractEntity
{
    private function __construct(
        public readonly string $reason,
        private readonly string $filesystemId,
        private GeolocationDbUpdateStatus $status = GeolocationDbUpdateStatus::IN_PROGRESS,
        private readonly Chronos $dateCreated = new Chronos(),
        private Chronos $dateUpdated = new Chronos(),
        private string|null $error = null,
    ) {
    }

    public static function withReason(string $reason): self
    {
        return new self($reason, self::currentFilesystemId());
    }

    public static function currentFilesystemId(): string
    {
        $system = stat(__FILE__);
        if (! $system) {
            throw new RuntimeException('It was not possible to resolve filesystem ID via stat function');
        }

        return (string) $system['dev'];
    }

    public function finishSuccessfully(): self
    {
        $this->dateUpdated = Chronos::now();
        $this->status = GeolocationDbUpdateStatus::SUCCESS;
        return $this;
    }

    public function finishWithError(string $error): self
    {
        $this->error = $error;
        $this->dateUpdated = Chronos::now();
        $this->status = GeolocationDbUpdateStatus::ERROR;
        return $this;
    }

    /**
     * @param positive-int $days
     */
    public function isOlderThan(int $days): bool
    {
        return Chronos::now()->greaterThan($this->dateUpdated->addDays($days));
    }

    public function isInProgress(): bool
    {
        return $this->status === GeolocationDbUpdateStatus::IN_PROGRESS;
    }

    public function isError(): bool
    {
        return $this->status === GeolocationDbUpdateStatus::ERROR;
    }

    public function isSuccess(): bool
    {
        return $this->status === GeolocationDbUpdateStatus::SUCCESS;
    }
}
