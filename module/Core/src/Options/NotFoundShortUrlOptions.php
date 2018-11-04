<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Zend\Stdlib\AbstractOptions;

class NotFoundShortUrlOptions extends AbstractOptions
{
    /**
     * @var bool
     */
    private $enableRedirection = false;
    /**
     * @var string|null
     */
    private $redirectTo;

    public function isRedirectionEnabled(): bool
    {
        return $this->enableRedirection;
    }

    protected function setEnableRedirection(bool $enableRedirection = true): self
    {
        $this->enableRedirection = $enableRedirection;
        return $this;
    }

    public function getRedirectTo(): string
    {
        return $this->redirectTo ?? '';
    }

    protected function setRedirectTo(?string $redirectTo): self
    {
        $this->redirectTo = $redirectTo;
        return $this;
    }
}
