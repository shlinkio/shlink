<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlVisited;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Paginator\Adapter\VisitsForTagPaginatorAdapter;
use Shlinkio\Shlink\Core\Paginator\Adapter\VisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

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

    public function track(ShortUrl $shortUrl, Visitor $visitor): void
    {
        $visit = new Visit($shortUrl, $visitor, $this->anonymizeRemoteAddr);

        $this->em->persist($visit);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new ShortUrlVisited($visit->getId(), $visitor->getRemoteAddress()));
    }

    /**
     * @return Visit[]|Paginator
     * @throws ShortUrlNotFoundException
     */
    public function info(ShortUrlIdentifier $identifier, VisitsParams $params, ?ApiKey $apiKey = null): Paginator
    {
        $spec = $apiKey !== null ? $apiKey->spec() : null;

        /** @var ShortUrlRepositoryInterface $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        if (! $repo->shortCodeIsInUse($identifier->shortCode(), $identifier->domain(), $spec)) {
            throw ShortUrlNotFoundException::fromNotFound($identifier);
        }

        /** @var VisitRepositoryInterface $repo */
        $repo = $this->em->getRepository(Visit::class);
        $paginator = new Paginator(new VisitsPaginatorAdapter($repo, $identifier, $params, $spec));
        $paginator->setMaxPerPage($params->getItemsPerPage())
                  ->setCurrentPage($params->getPage());

        return $paginator;
    }

    /**
     * @return Visit[]|Paginator
     * @throws TagNotFoundException
     */
    public function visitsForTag(string $tag, VisitsParams $params, ?ApiKey $apiKey = null): Paginator
    {
        /** @var TagRepository $tagRepo */
        $tagRepo = $this->em->getRepository(Tag::class);
        if (! $tagRepo->tagExists($tag, $apiKey)) {
            throw TagNotFoundException::fromTag($tag);
        }

        /** @var VisitRepositoryInterface $repo */
        $repo = $this->em->getRepository(Visit::class);
        $paginator = new Paginator(new VisitsForTagPaginatorAdapter($repo, $tag, $params, $apiKey));
        $paginator->setMaxPerPage($params->getItemsPerPage())
                  ->setCurrentPage($params->getPage());

        return $paginator;
    }
}
