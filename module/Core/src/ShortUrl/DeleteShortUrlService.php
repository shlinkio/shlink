<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Options\DeleteShortUrlsOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DeleteShortUrlService implements DeleteShortUrlServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DeleteShortUrlsOptions $deleteShortUrlsOptions,
        private readonly ShortUrlResolverInterface $urlResolver,
    ) {
    }

    /**
     * @throws Exception\ShortUrlNotFoundException
     * @throws Exception\DeleteShortUrlException
     */
    public function deleteByShortCode(
        ShortUrlIdentifier $identifier,
        bool $ignoreThreshold = false,
        ?ApiKey $apiKey = null,
    ): void {
        $shortUrl = $this->urlResolver->resolveShortUrl($identifier, $apiKey);
        if (! $ignoreThreshold && $this->isThresholdReached($shortUrl)) {
            throw Exception\DeleteShortUrlException::fromVisitsThreshold(
                $this->deleteShortUrlsOptions->visitsThreshold,
                $identifier,
            );
        }

        $this->em->remove($shortUrl);
        $this->em->flush();
    }

    private function isThresholdReached(ShortUrl $shortUrl): bool
    {
        if (! $this->deleteShortUrlsOptions->checkVisitsThreshold) {
            return false;
        }

        return $shortUrl->getVisitsCount() >= $this->deleteShortUrlsOptions->visitsThreshold;
    }
}
