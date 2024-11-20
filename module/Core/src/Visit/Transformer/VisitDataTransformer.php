<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Transformer;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

readonly class VisitDataTransformer implements VisitDataTransformerInterface
{
    public function __construct(private ShortUrlStringifierInterface $stringifier)
    {
    }

    public function transform(Visit $visit): array
    {
        return $visit->toArray(fn (ShortUrl $shortUrl) => [
            'shortCode' => $shortUrl->getShortCode(),
            'domain' => $shortUrl->getDomain()?->authority,
            'shortUrl' => $this->stringifier->stringify($shortUrl),
        ]);
    }
}
