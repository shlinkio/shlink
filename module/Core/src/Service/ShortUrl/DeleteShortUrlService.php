<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Options\DeleteShortUrlsOptions;

class DeleteShortUrlService implements DeleteShortUrlServiceInterface
{
    use FindShortCodeTrait;

    private EntityManagerInterface $em;
    private DeleteShortUrlsOptions $deleteShortUrlsOptions;

    public function __construct(EntityManagerInterface $em, DeleteShortUrlsOptions $deleteShortUrlsOptions)
    {
        $this->em = $em;
        $this->deleteShortUrlsOptions = $deleteShortUrlsOptions;
    }

    /**
     * @throws Exception\ShortUrlNotFoundException
     * @throws Exception\DeleteShortUrlException
     */
    public function deleteByShortCode(string $shortCode, bool $ignoreThreshold = false): void
    {
        $shortUrl = $this->findByShortCode($this->em, $shortCode);
        if (! $ignoreThreshold && $this->isThresholdReached($shortUrl)) {
            throw Exception\DeleteShortUrlException::fromVisitsThreshold(
                $this->deleteShortUrlsOptions->getVisitsThreshold(),
                $shortUrl->getShortCode(),
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
