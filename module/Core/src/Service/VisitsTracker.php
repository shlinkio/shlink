<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use function sprintf;

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
     * Tracks a new visit to provided short code from provided visitor
     */
    public function track(string $shortCode, Visitor $visitor): void
    {
        /** @var ShortUrl $shortUrl */
        $shortUrl = $this->em->getRepository(ShortUrl::class)->findOneBy([
            'shortCode' => $shortCode,
        ]);

        $visit = new Visit($shortUrl, $visitor);

        /** @var ORM\EntityManager $em */
        $em = $this->em;
        $em->persist($visit);
        $em->flush($visit);
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
