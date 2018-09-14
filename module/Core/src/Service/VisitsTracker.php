<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Repository\VisitRepository;

class VisitsTracker implements VisitsTrackerInterface
{
    /**
     * @var ORM\EntityManagerInterface
     */
    private $em;

    public function __construct(ORM\EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Tracks a new visit to provided short code, using an array of data to look up information
     *
     * @param string $shortCode
     * @param ServerRequestInterface $request
     * @throws ORM\ORMInvalidArgumentException
     * @throws ORM\OptimisticLockException
     */
    public function track($shortCode, ServerRequestInterface $request): void
    {
        /** @var ShortUrl $shortUrl */
        $shortUrl = $this->em->getRepository(ShortUrl::class)->findOneBy([
            'shortCode' => $shortCode,
        ]);

        $visit = new Visit();
        $visit->setShortUrl($shortUrl)
              ->setUserAgent($request->getHeaderLine('User-Agent'))
              ->setReferer($request->getHeaderLine('Referer'))
              ->setRemoteAddr($this->findOutRemoteAddr($request));

        /** @var ORM\EntityManager $em */
        $em = $this->em;
        $em->persist($visit);
        $em->flush($visit);
    }

    /**
     * @param ServerRequestInterface $request
     */
    private function findOutRemoteAddr(ServerRequestInterface $request): ?string
    {
        $forwardedFor = $request->getHeaderLine('X-Forwarded-For');
        if (empty($forwardedFor)) {
            $serverParams = $request->getServerParams();
            return $serverParams['REMOTE_ADDR'] ?? null;
        }

        $ips = \explode(',', $forwardedFor);
        return $ips[0] ?? null;
    }

    /**
     * Returns the visits on certain short code
     *
     * @param string $shortCode
     * @param DateRange $dateRange
     * @return Visit[]
     * @throws InvalidArgumentException
     */
    public function info(string $shortCode, DateRange $dateRange = null): array
    {
        /** @var ShortUrl|null $shortUrl */
        $shortUrl = $this->em->getRepository(ShortUrl::class)->findOneBy([
            'shortCode' => $shortCode,
        ]);
        if ($shortUrl === null) {
            throw new InvalidArgumentException(sprintf('Short code "%s" not found', $shortCode));
        }

        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);
        return $repo->findVisitsByShortUrl($shortUrl, $dateRange);
    }
}
