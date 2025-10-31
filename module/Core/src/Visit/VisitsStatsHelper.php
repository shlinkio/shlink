<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Adapter\AdapterInterface;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepository;
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Repository\TagRepository;
use Shlinkio\Shlink\Core\Visit\Entity\OrphanVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\ShortUrlVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;
use Shlinkio\Shlink\Core\Visit\Model\WithDomainVisitsParams;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\DomainVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\NonOrphanVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\OrphanVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\ShortUrlVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\TagVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Repository\OrphanVisitsCountRepository;
use Shlinkio\Shlink\Core\Visit\Repository\ShortUrlVisitsCountRepository;
use Shlinkio\Shlink\Core\Visit\Repository\VisitRepository;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

readonly class VisitsStatsHelper implements VisitsStatsHelperInterface
{
    public function __construct(private EntityManagerInterface $em, private UrlShortenerOptions $options)
    {
    }

    public function getVisitsStats(ApiKey|null $apiKey = null): VisitsStats
    {
        /** @var OrphanVisitsCountRepository $orphanVisitsCountRepo */
        $orphanVisitsCountRepo = $this->em->getRepository(OrphanVisitsCount::class);
        /** @var ShortUrlVisitsCountRepository $visitsCountRepo */
        $visitsCountRepo = $this->em->getRepository(ShortUrlVisitsCount::class);

        return new VisitsStats(
            nonOrphanVisitsTotal: $visitsCountRepo->countNonOrphanVisits(new VisitsCountFiltering(apiKey: $apiKey)),
            orphanVisitsTotal: $orphanVisitsCountRepo->countOrphanVisits(
                new OrphanVisitsCountFiltering(apiKey: $apiKey),
            ),
            nonOrphanVisitsNonBots: $visitsCountRepo->countNonOrphanVisits(
                new VisitsCountFiltering(excludeBots: true, apiKey: $apiKey),
            ),
            orphanVisitsNonBots: $orphanVisitsCountRepo->countOrphanVisits(
                new OrphanVisitsCountFiltering(excludeBots: true, apiKey: $apiKey),
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function visitsForShortUrl(
        ShortUrlIdentifier $identifier,
        VisitsParams $params,
        ApiKey|null $apiKey = null,
    ): Paginator {
        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        if (! $repo->shortCodeIsInUse($identifier, $apiKey?->spec())) {
            throw ShortUrlNotFoundException::fromNotFound($identifier);
        }

        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);

        return $this->createPaginator(
            new ShortUrlVisitsPaginatorAdapter($repo, $identifier, $params, $apiKey),
            $params,
        );
    }

    /**
     * @inheritDoc
     */
    public function visitsForTag(string $tag, WithDomainVisitsParams $params, ApiKey|null $apiKey = null): Paginator
    {
        /** @var TagRepository $tagRepo */
        $tagRepo = $this->em->getRepository(Tag::class);
        if (! $tagRepo->tagExists($tag, $apiKey)) {
            throw TagNotFoundException::fromTag($tag);
        }

        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);

        return $this->createPaginator(new TagVisitsPaginatorAdapter($repo, $tag, $params, $apiKey), $params);
    }

    /**
     * @inheritDoc
     */
    public function visitsForDomain(string $domain, VisitsParams $params, ApiKey|null $apiKey = null): Paginator
    {
        /** @var DomainRepository $domainRepo */
        $domainRepo = $this->em->getRepository(Domain::class);
        if ($domain !== Domain::DEFAULT_AUTHORITY && ! $domainRepo->domainExists($domain, $apiKey)) {
            throw DomainNotFoundException::fromAuthority($domain);
        }

        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);

        return $this->createPaginator(new DomainVisitsPaginatorAdapter($repo, $domain, $params, $apiKey), $params);
    }

    /**
     * @inheritDoc
     */
    public function orphanVisits(OrphanVisitsParams $params, ApiKey|null $apiKey = null): Paginator
    {
        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);

        return $this->createPaginator(
            new OrphanVisitsPaginatorAdapter($repo, $params, $apiKey, $this->options),
            $params,
        );
    }

    public function nonOrphanVisits(WithDomainVisitsParams $params, ApiKey|null $apiKey = null): Paginator
    {
        /** @var VisitRepository $repo */
        $repo = $this->em->getRepository(Visit::class);

        return $this->createPaginator(new NonOrphanVisitsPaginatorAdapter($repo, $params, $apiKey), $params);
    }

    /**
     * @param AdapterInterface<Visit> $adapter
     * @return Paginator<Visit>
     */
    private function createPaginator(AdapterInterface $adapter, VisitsParams $params): Paginator
    {
        $paginator = new Paginator($adapter);
        $paginator->setMaxPerPage($params->itemsPerPage)
                  ->setCurrentPage($params->page);

        return $paginator;
    }
}
