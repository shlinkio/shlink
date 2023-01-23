<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Shlinkio\Shlink\Core\Exception\InvalidUrlException;

/** @deprecated */
interface UrlValidatorInterface
{
    /**
     * @deprecated
     * @throws InvalidUrlException
     */
    public function validateUrl(string $url, bool $doValidate): void;

    /**
     * @deprecated
     * @throws InvalidUrlException
     */
    public function validateUrlWithTitle(string $url, bool $doValidate): ?string;
}
