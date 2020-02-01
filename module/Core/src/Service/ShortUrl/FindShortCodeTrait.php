<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;

trait FindShortCodeTrait
{
    /**
     * @throws ShortUrlNotFoundException
     */
    private function findByShortCode(EntityManagerInterface $em, ShortUrlIdentifier $identifier): ShortUrl
    {
        /** @var ShortUrlRepositoryInterface $repo */
        $repo = $em->getRepository(ShortUrl::class);
        $shortUrl = $repo->findOneByShortCode($identifier->shortCode(), $identifier->domain());
        if ($shortUrl === null) {
            throw ShortUrlNotFoundException::fromNotFound($identifier);
        }

        return $shortUrl;
    }
}
