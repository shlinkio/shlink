<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Options\DeleteShortUrlsOptions;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DeleteShortUrlService implements DeleteShortUrlServiceInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private DeleteShortUrlsOptions $deleteShortUrlsOptions,
        private ShortUrlResolverInterface $urlResolver,
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
                $this->deleteShortUrlsOptions->getVisitsThreshold(),
                $identifier,
            );
        }

        $this->em->remove($shortUrl);
        $this->em->flush();
    }

    private function isThresholdReached(ShortUrl $shortUrl): bool
    {
        if (! $this->deleteShortUrlsOptions->doCheckVisitsThreshold()) {
            return false;
        }

        return $shortUrl->getVisitsCount() >= $this->deleteShortUrlsOptions->getVisitsThreshold();
    }
}
