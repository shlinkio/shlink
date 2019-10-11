<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Zend\Stdlib\AbstractOptions;

class UrlShortenerOptions extends AbstractOptions
{
    // phpcs:disable
    protected $__strictMode__ = false;
    // phpcs:enable

    private $validateUrl = true;

    public function isUrlValidationEnabled(): bool
    {
        return $this->validateUrl;
    }

    protected function setValidateUrl($validateUrl): self
    {
        $this->validateUrl = (bool) $validateUrl;
        return $this;
    }
}
