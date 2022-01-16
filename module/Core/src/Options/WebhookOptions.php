<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Laminas\Stdlib\AbstractOptions;

class WebhookOptions extends AbstractOptions
{
    protected $__strictMode__ = false; // phpcs:ignore

    private array $webhooks = [];
    private bool $notifyOrphanVisitsToWebhooks = false;

    public function webhooks(): array
    {
        return $this->webhooks;
    }

    public function hasWebhooks(): bool
    {
        return ! empty($this->webhooks);
    }

    protected function setWebhooks(array $webhooks): void
    {
        $this->webhooks = $webhooks;
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
