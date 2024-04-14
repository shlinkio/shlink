<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Transformer;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlWithVisitsSummary;

/**
 * @fixme Do not implement DataTransformerInterface, but a separate interface
 */
readonly class ShortUrlDataTransformer implements DataTransformerInterface
{
    public function __construct(private ShortUrlStringifierInterface $stringifier)
    {
    }

    /**
     * @param ShortUrlWithVisitsSummary|ShortUrl $data
     */
    public function transform($data): array // phpcs:ignore
    {
        $shortUrl = $data instanceof ShortUrlWithVisitsSummary ? $data->shortUrl : $data;
        return [
            'shortUrl' => $this->stringifier->stringify($shortUrl),
            ...$data->toArray(),
        ];
    }
}
