<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Repository\VisitRepository;

class VisitsTracker implements VisitsTrackerInterface
{
    /**
     * @var EntityManagerInterface|EntityManager
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Tracks a new visit to provided short code, using an array of data to look up information
     *
     * @param string $shortCode
     * @param ServerRequestInterface $request
     */
    public function track($shortCode, ServerRequestInterface $request)
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

        $this->em->persist($visit);
        $this->em->flush($visit);
    }

    /**
     * @param ServerRequestInterface $request
     * @return string|null
     */
    private function findOutRemoteAddr(ServerRequestInterface $request)
    {
        $forwardedFor = $request->getHeaderLine('X-Forwarded-For');
        if (empty($forwardedFor)) {
            $serverParams = $request->getServerParams();
            return $serverParams['REMOTE_ADDR'] ?? null;
        }

        $ips = explode(',', $forwardedFor);
        return $ips[0] ?? null;
    }

    /**
     * Returns the visits on certain short code
     *
     * @param $shortCode
     * @param DateRange $dateRange
     * @return Visit[]
     * @throws InvalidArgumentException
     */
    public function info($shortCode, DateRange $dateRange = null): array
    {
        /** @var ShortUrl $shortUrl */
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
