<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;

trait FindShortCodeTrait
{
    /**
     * @param string $shortCode
     * @return ShortUrl
     * @throws ShortUrlNotFoundException
     */
    private function findByShortCode(EntityManagerInterface $em, string $shortCode): ShortUrl
    {
        /** @var ShortUrl|null $shortUrl */
        $shortUrl = $em->getRepository(ShortUrl::class)->findOneBy([
            'shortCode' => $shortCode,
        ]);
        if ($shortUrl === null) {
            throw ShortUrlNotFoundException::fromNotFoundShortCode($shortCode);
        }

        return $shortUrl;
    }
}
