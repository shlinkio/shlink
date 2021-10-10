<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Laminas\Stdlib\AbstractOptions;

use const Shlinkio\Shlink\DEFAULT_DELETE_SHORT_URL_THRESHOLD;

class DeleteShortUrlsOptions extends AbstractOptions
{
    private int $visitsThreshold = DEFAULT_DELETE_SHORT_URL_THRESHOLD;
    private bool $checkVisitsThreshold = true;

    public function getVisitsThreshold(): int
    {
        return $this->visitsThreshold;
    }

    protected function setVisitsThreshold(int $visitsThreshold): self
    {
        $this->visitsThreshold = $visitsThreshold;
        return $this;
    }

    public function doCheckVisitsThreshold(): bool
    {
        return $this->checkVisitsThreshold;
    }

    protected function setCheckVisitsThreshold(bool $checkVisitsThreshold): self
    {
        $this->checkVisitsThreshold = $checkVisitsThreshold;
        return $this;
    }
}
