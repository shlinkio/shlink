<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Config\Options\DeleteShortUrlsOptions;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ExpiredShortUrlsConditions;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ExpiredShortUrlsRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

readonly class DeleteShortUrlService implements DeleteShortUrlServiceInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private DeleteShortUrlsOptions $deleteShortUrlsOptions,
        private ShortUrlResolverInterface $urlResolver,
        private ExpiredShortUrlsRepositoryInterface $expiredShortUrlsRepository,
    ) {
    }

    /**
     * @throws Exception\ShortUrlNotFoundException
     * @throws Exception\DeleteShortUrlException
     */
    public function deleteByShortCode(
        ShortUrlIdentifier $identifier,
        bool $ignoreThreshold = false,
        ApiKey|null $apiKey = null,
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
        return $this->deleteShortUrlsOptions->checkVisitsThreshold && $shortUrl->reachedVisits(
            $this->deleteShortUrlsOptions->visitsThreshold,
        );
    }

    public function deleteExpiredShortUrls(ExpiredShortUrlsConditions $conditions): int
    {
        return $this->expiredShortUrlsRepository->delete($conditions);
    }

    public function countExpiredShortUrls(ExpiredShortUrlsConditions $conditions): int
    {
        return $this->expiredShortUrlsRepository->dryCount($conditions);
    }
}
