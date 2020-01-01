<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM;
use Laminas\Paginator\Paginator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\ShortUrlVisited;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Paginator\Adapter\VisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Repository\VisitRepository;

class VisitsTracker implements VisitsTrackerInterface
{
    private ORM\EntityManagerInterface $em;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(ORM\EntityManagerInterface $em, EventDispatcherInterface $eventDispatcher)
    {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
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

        $this->em->persist($visit);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new ShortUrlVisited($visit->getId()));
    }

    /**
     * Returns the visits on certain short code
     *
     * @return Visit[]|Paginator
     * @throws ShortUrlNotFoundException
     */
    public function info(string $shortCode, VisitsParams $params): Paginator
    {
        /** @var ORM\EntityRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        if ($repo->count(['shortCode' => $shortCode]) < 1) {
            throw ShortUrlNotFoundException::fromNotFoundShortCode($shortCode);
        }

        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);
        $paginator = new Paginator(new VisitsPaginatorAdapter($repo, $shortCode, $params));
        $paginator->setItemCountPerPage($params->getItemsPerPage())
                  ->setCurrentPageNumber($params->getPage());

        return $paginator;
    }
}
