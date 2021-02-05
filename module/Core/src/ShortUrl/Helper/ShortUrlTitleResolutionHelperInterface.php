<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Shlinkio\Shlink\Core\Exception\InvalidUrlException;

interface ShortUrlTitleResolutionHelperInterface
{
    /**
     * @throws InvalidUrlException
     */
    public function processTitleAndValidateUrl(TitleResolutionModelInterface $data): TitleResolutionModelInterface;
}
