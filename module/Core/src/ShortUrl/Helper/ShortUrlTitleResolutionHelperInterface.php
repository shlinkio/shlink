<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Shlinkio\Shlink\Core\Exception\InvalidUrlException;

interface ShortUrlTitleResolutionHelperInterface
{
    /**
     * @deprecated TODO Rename to processTitle once URL validation is removed with Shlink 4.0.0
     * @template T of TitleResolutionModelInterface
     * @param T $data
     * @return T
     * @throws InvalidUrlException
     */
    public function processTitleAndValidateUrl(TitleResolutionModelInterface $data): TitleResolutionModelInterface;
}
