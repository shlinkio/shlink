<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Zend\Stdlib\AbstractOptions;

class NotFoundRedirectOptions extends AbstractOptions
{
    /** @var string|null */
    private $invalidShortUrl;
    /** @var string|null */
    private $regular404;
    /** @var string|null */
    private $baseUrl;

    public function getInvalidShortUrlRedirect(): ?string
    {
        return $this->invalidShortUrl;
    }

    public function hasInvalidShortUrlRedirect(): bool
    {
        return $this->invalidShortUrl !== null;
    }

    protected function setInvalidShortUrl(?string $invalidShortUrl): self
    {
        $this->invalidShortUrl = $invalidShortUrl;
        return $this;
    }

    public function getRegular404Redirect(): ?string
    {
        return $this->regular404;
    }

    public function hasRegular404Redirect(): bool
    {
        return $this->regular404 !== null;
    }

    protected function setRegular404(?string $regular404): self
    {
        $this->regular404 = $regular404;
        return $this;
    }

    public function getBaseUrlRedirect(): ?string
    {
        return $this->baseUrl;
    }

    public function hasBaseUrlRedirect(): bool
    {
        return $this->baseUrl !== null;
    }

    protected function setBaseUrl(?string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }
}
