<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Zend\Stdlib\AbstractOptions;

class DeleteShortUrlsOptions extends AbstractOptions
{
    private $visitsThreshold = 15;
    private $checkVisitsThreshold = true;

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
