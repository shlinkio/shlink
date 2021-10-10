<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Transformer;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;

use function Functional\invoke;
use function Functional\invoke_if;

class ShortUrlDataTransformer implements DataTransformerInterface
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
            'shortCode' => $shortUrl->getShortCode(),
            'shortUrl' => $this->stringifier->stringify($shortUrl),
            'longUrl' => $shortUrl->getLongUrl(),
            'dateCreated' => $shortUrl->getDateCreated()->toAtomString(),
            'visitsCount' => $shortUrl->getVisitsCount(),
            'tags' => invoke($shortUrl->getTags(), '__toString'),
            'meta' => $this->buildMeta($shortUrl),
            'domain' => $shortUrl->getDomain(),
            'title' => $shortUrl->title(),
            'crawlable' => $shortUrl->crawlable(),
            'forwardQuery' => $shortUrl->forwardQuery(),
        ];
    }

    private function buildMeta(ShortUrl $shortUrl): array
    {
        $validSince = $shortUrl->getValidSince();
        $validUntil = $shortUrl->getValidUntil();
        $maxVisits = $shortUrl->getMaxVisits();

        return [
            'validSince' => invoke_if($validSince, 'toAtomString'),
            'validUntil' => invoke_if($validUntil, 'toAtomString'),
            'maxVisits' => $maxVisits,
        ];
    }
}
