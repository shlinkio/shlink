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
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Paginator\Adapter\VisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Repository\VisitRepository;

class VisitsTracker implements VisitsTrackerInterface
{
    private ORM\EntityManagerInterface $em;
    private EventDispatcherInterface $eventDispatcher;
    private bool $anonymizeRemoteAddr;

    public function __construct(
        ORM\EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        bool $anonymizeRemoteAddr
    ) {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->anonymizeRemoteAddr = $anonymizeRemoteAddr;
    }

    /**
     * Tracks a new visit to provided short code from provided visitor
     */
    public function track(ShortUrl $shortUrl, Visitor $visitor): void
    {
        $visit = new Visit($shortUrl, $visitor, $this->anonymizeRemoteAddr);

        $this->em->persist($visit);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new ShortUrlVisited($visit->getId(), $visitor->getRemoteAddress()));
    }

    /**
     * Returns the visits on certain short code
     *
     * @return Visit[]|Paginator
     * @throws ShortUrlNotFoundException
     */
    public function info(ShortUrlIdentifier $identifier, VisitsParams $params): Paginator
    {
        /** @var ShortUrlRepositoryInterface $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        if (! $repo->shortCodeIsInUse($identifier->shortCode(), $identifier->domain())) {
            throw ShortUrlNotFoundException::fromNotFound($identifier);
        }

        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);
        $paginator = new Paginator(new VisitsPaginatorAdapter($repo, $identifier, $params));
        $paginator->setItemCountPerPage($params->getItemsPerPage())
                  ->setCurrentPageNumber($params->getPage());

        return $paginator;
    }
}
