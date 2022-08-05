<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Laminas\Stdlib\AbstractOptions;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;

class UrlShortenerOptions extends AbstractOptions
{
    protected $__strictMode__ = false; // phpcs:ignore

    private array $domain = [];
    private int $defaultShortCodesLength = DEFAULT_SHORT_CODES_LENGTH;
    private bool $autoResolveTitles = false;
    private bool $appendExtraPath = false;
    private bool $multiSegmentSlugsEnabled = false;

    public function domain(): array
    {
        return $this->domain;
    }

    protected function setDomain(array $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    public function defaultShortCodesLength(): int
    {
        return $this->defaultShortCodesLength;
    }

    protected function setDefaultShortCodesLength(int $defaultShortCodesLength): self
    {
        $this->defaultShortCodesLength = $defaultShortCodesLength;
        return $this;
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

    public function multiSegmentSlugsEnabled(): bool
    {
        return $this->multiSegmentSlugsEnabled;
    }

    protected function setMultiSegmentSlugsEnabled(bool $multiSegmentSlugsEnabled): void
    {
        $this->multiSegmentSlugsEnabled = $multiSegmentSlugsEnabled;
    }
}
