<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Transformer;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlWithVisitsSummary;

readonly class ShortUrlDataTransformer implements ShortUrlDataTransformerInterface
{
    public function __construct(private ShortUrlStringifierInterface $stringifier)
    {
    }

    public function transform(ShortUrlWithVisitsSummary|ShortUrl $data): array
    {
        $shortUrl = $data instanceof ShortUrlWithVisitsSummary ? $data->shortUrl : $data;
        return [
            'shortUrl' => $this->stringifier->stringify($shortUrl),
            ...$data->toArray(),
        ];
    }
}
