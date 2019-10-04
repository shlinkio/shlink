<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Transformer;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;

use function Functional\invoke;
use function Functional\invoke_if;

class ShortUrlDataTransformer implements DataTransformerInterface
{
    /** @var array */
    private $domainConfig;

    public function __construct(array $domainConfig)
    {
        $this->domainConfig = $domainConfig;
    }

    /**
     * @param ShortUrl $shortUrl
     */
    public function transform($shortUrl): array
    {
        $longUrl = $shortUrl->getLongUrl();

        return [
            'shortCode' => $shortUrl->getShortCode(),
            'shortUrl' => $shortUrl->toString($this->domainConfig),
            'longUrl' => $longUrl,
            'dateCreated' => $shortUrl->getDateCreated()->toAtomString(),
            'visitsCount' => $shortUrl->getVisitsCount(),
            'tags' => invoke($shortUrl->getTags(), '__toString'),
            'meta' => $this->buildMeta($shortUrl),

            // Deprecated
            'originalUrl' => $longUrl,
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
