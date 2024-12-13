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
        private string|null $filename = null,
        private string|null $error = null,
    ) {
    }

    public static function createForCurrentFilesystem(): self
    {
        return new self(stat(__FILE__)['dev']);
    }

    public function finishSuccessfully(string $filename): void
    {
        $this->dateUpdated = Chronos::now();
        $this->filename = $filename;
        $this->status = GeolocationDbUpdateStatus::SUCCESS;
    }

    public function finishWithError(string $error): void
    {
        $this->dateUpdated = Chronos::now();
        $this->error = $error;
        $this->status = GeolocationDbUpdateStatus::ERROR;
    }
}
