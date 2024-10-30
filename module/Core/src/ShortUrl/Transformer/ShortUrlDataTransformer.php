<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Transformer;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlWithDeps;

readonly class ShortUrlDataTransformer implements ShortUrlDataTransformerInterface
{
    public function __construct(private ShortUrlStringifierInterface $stringifier)
    {
    }

    public function transform(ShortUrlWithDeps|ShortUrl $shortUrl): array
    {
        $shortUrlIdentifier = $shortUrl instanceof ShortUrl
            ? ShortUrlIdentifier::fromShortUrl($shortUrl)
            : $shortUrl->toIdentifier();
        return [
            'shortUrl' => $this->stringifier->stringify($shortUrlIdentifier),
            ...$shortUrl->toArray(),
        ];
    }
}
