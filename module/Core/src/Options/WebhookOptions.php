<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Laminas\Stdlib\AbstractOptions;

class WebhookOptions extends AbstractOptions
{
    protected $__strictMode__ = false; // phpcs:ignore

    private array $visitsWebhooks = [];
    private bool $notifyOrphanVisitsToWebhooks = false;

    public function webhooks(): array
    {
        return $this->visitsWebhooks;
    }

    public function hasWebhooks(): bool
    {
        return ! empty($this->visitsWebhooks);
    }

    protected function setVisitsWebhooks(array $visitsWebhooks): void
    {
        $this->visitsWebhooks = $visitsWebhooks;
    }

    public function notifyOrphanVisits(): bool
    {
        return $this->notifyOrphanVisitsToWebhooks;
    }

    protected function setNotifyOrphanVisitsToWebhooks(bool $notifyOrphanVisitsToWebhooks): void
    {
        $this->notifyOrphanVisitsToWebhooks = $notifyOrphanVisitsToWebhooks;
    }
}
