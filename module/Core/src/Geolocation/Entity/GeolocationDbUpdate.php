<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Geolocation\Entity;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;

use function stat;

class GeolocationDbUpdate extends AbstractEntity
{
    private function __construct(
        private readonly string $filesystemId,
        private GeolocationDbUpdateStatus $status = GeolocationDbUpdateStatus::IN_PROGRESS,
        private readonly Chronos $dateCreated = new Chronos(),
        private Chronos $dateUpdated = new Chronos(),
        private string|null $error = null,
    ) {
    }

    public static function forFilesystemId(string|null $filesystemId = null): self
    {
        return new self($filesystemId ?? self::currentFilesystemId());
    }

    public static function currentFilesystemId(): string
    {
        $system = stat(__FILE__);
        if (! $system) {
            // TODO Throw error
        }

        return (string) $system['dev'];
    }

    public function finishSuccessfully(): void
    {
        $this->dateUpdated = Chronos::now();
        $this->status = GeolocationDbUpdateStatus::SUCCESS;
    }

    public function finishWithError(string $error): void
    {
        $this->error = $error;
        $this->dateUpdated = Chronos::now();
        $this->status = GeolocationDbUpdateStatus::ERROR;
    }

    /**
     * This update would require a new download if:
     * - It is successful and older than 30 days
     * - It is error and older than 2 days
     */
    public function needsUpdate(): bool
    {
        return match ($this->status) {
            GeolocationDbUpdateStatus::SUCCESS => Chronos::now()->greaterThan($this->dateUpdated->addDays(30)),
            GeolocationDbUpdateStatus::ERROR => Chronos::now()->greaterThan($this->dateUpdated->addDays(2)),
            default => false,
        };
    }

    public function isInProgress(): bool
    {
        return $this->status === GeolocationDbUpdateStatus::IN_PROGRESS;
    }

    public function isError(): bool
    {
        return $this->status === GeolocationDbUpdateStatus::ERROR;
    }
}
