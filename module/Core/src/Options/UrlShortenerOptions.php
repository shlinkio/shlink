<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Laminas\Stdlib\AbstractOptions;

class UrlShortenerOptions extends AbstractOptions
{
    protected $__strictMode__ = false; // phpcs:ignore

    private bool $validateUrl = true;
    private bool $autoResolveTitles = false;
    private bool $appendExtraPath = false;

    public function isUrlValidationEnabled(): bool
    {
        return $this->validateUrl;
    }

    protected function setValidateUrl(bool $validateUrl): void
    {
        $this->validateUrl = $validateUrl;
    }

    public function autoResolveTitles(): bool
    {
        return $this->autoResolveTitles;
    }

    protected function setAutoResolveTitles(bool $autoResolveTitles): void
    {
        $this->autoResolveTitles = $autoResolveTitles;
    }

    public function appendExtraPath(): bool
    {
        return $this->appendExtraPath;
    }

    protected function setAppendExtraPath(bool $appendExtraPath): void
    {
        $this->appendExtraPath = $appendExtraPath;
    }
}
