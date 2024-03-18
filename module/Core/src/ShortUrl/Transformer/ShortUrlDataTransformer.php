<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Transformer;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;

readonly class ShortUrlDataTransformer implements DataTransformerInterface
{
    public function __construct(private ShortUrlStringifierInterface $stringifier)
    {
    }

    /**
     * @param ShortUrl $shortUrl
     */
    public function transform($shortUrl): array // phpcs:ignore
    {
        return [
            'shortUrl' => $this->stringifier->stringify($shortUrl),
            ...$shortUrl->toArray(),
        ];
    }
}
