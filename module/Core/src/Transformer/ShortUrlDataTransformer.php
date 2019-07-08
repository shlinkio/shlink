<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Transformer;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Util\ShortUrlBuilderTrait;

use function Functional\invoke;
use function Functional\invoke_if;

class ShortUrlDataTransformer implements DataTransformerInterface
{
    use ShortUrlBuilderTrait;

    /** @var array */
    private $domainConfig;

    public function __construct(array $domainConfig)
    {
        $this->domainConfig = $domainConfig;
    }

    /**
     * @param ShortUrl $value
     */
    public function transform($value): array
    {
        $longUrl = $value->getLongUrl();
        $shortCode = $value->getShortCode();

        return [
            'shortCode' => $shortCode,
            'shortUrl' => $this->buildShortUrl($this->domainConfig, $shortCode),
            'longUrl' => $longUrl,
            'dateCreated' => $value->getDateCreated()->toAtomString(),
            'visitsCount' => $value->getVisitsCount(),
            'tags' => invoke($value->getTags(), '__toString'),
            'meta' => $this->buildMeta($value),

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
