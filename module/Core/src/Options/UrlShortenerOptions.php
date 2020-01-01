<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Zend\Stdlib\AbstractOptions;

class UrlShortenerOptions extends AbstractOptions
{
    protected $__strictMode__ = false; // phpcs:ignore

    private bool $validateUrl = true;

    public function isUrlValidationEnabled(): bool
    {
        return $this->validateUrl;
    }

    protected function setValidateUrl(bool $validateUrl): self
    {
        $this->validateUrl = $validateUrl;
        return $this;
    }
}
