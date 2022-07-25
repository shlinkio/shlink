<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Laminas\Stdlib\AbstractOptions;

class RabbitMqOptions extends AbstractOptions
{
    protected $__strictMode__ = false; // phpcs:ignore

    private bool $enabled = false;
    /** @deprecated */
    private bool $legacyVisitsPublishing = false;

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    protected function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /** @deprecated */
    public function legacyVisitsPublishing(): bool
    {
        return $this->legacyVisitsPublishing;
    }

    /** @deprecated */
    protected function setLegacyVisitsPublishing(bool $legacyVisitsPublishing): self
    {
        $this->legacyVisitsPublishing = $legacyVisitsPublishing;
        return $this;
    }
}
