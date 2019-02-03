<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Zend\Stdlib\AbstractOptions;

class UrlShortenerOptions extends AbstractOptions
{
    public const DEFAULT_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    // phpcs:disable
    protected $__strictMode__ = false;
    // phpcs:enable

    private $shortcodeChars = self::DEFAULT_CHARS;
    private $validateUrl = true;

    public function getChars(): string
    {
        return $this->shortcodeChars;
    }

    protected function setShortcodeChars(string $shortcodeChars): self
    {
        $this->shortcodeChars = empty($shortcodeChars) ? self::DEFAULT_CHARS : $shortcodeChars;
        return $this;
    }

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
