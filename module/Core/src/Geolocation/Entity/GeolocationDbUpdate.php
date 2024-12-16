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
        private readonly string $reason,
        private GeolocationDbUpdateStatus $status = GeolocationDbUpdateStatus::IN_PROGRESS,
        private readonly Chronos $dateCreated = new Chronos(),
        private Chronos $dateUpdated = new Chronos(),
        private string|null $error = null,
    ) {
    }

    public static function withReason(string $reason, string|null $filesystemId = null): self
    {
        return new self($reason, $filesystemId ?? self::currentFilesystemId());
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
