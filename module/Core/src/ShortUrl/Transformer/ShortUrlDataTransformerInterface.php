<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Transformer;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlWithDeps;

interface ShortUrlDataTransformerInterface
{
    public function transform(ShortUrlWithDeps|ShortUrl $shortUrl): array;
}
