<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

interface ShortUrlTitleResolutionHelperInterface
{
    /**
     * @template T of TitleResolutionModelInterface
     * @param T $data
     * @return T
     */
    public function processTitle(TitleResolutionModelInterface $data): TitleResolutionModelInterface;
}
