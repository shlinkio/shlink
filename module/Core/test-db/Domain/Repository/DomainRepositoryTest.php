<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Domain\Repository;

use Shlinkio\Shlink\Core\Domain\Repository\DomainRepository;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

class DomainRepositoryTest extends DatabaseTestCase
{
    protected const ENTITIES_TO_EMPTY = [ShortUrl::class, Domain::class];

    private DomainRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Domain::class);
    }

    /** @test */
    public function findDomainsReturnsExpectedResult(): void
    {
        $fooDomain = new Domain('foo.com');
        $this->getEntityManager()->persist($fooDomain);
        $fooShortUrl = $this->createShortUrl($fooDomain);
        $this->getEntityManager()->persist($fooShortUrl);

        $barDomain = new Domain('bar.com');
        $this->getEntityManager()->persist($barDomain);
        $barShortUrl = $this->createShortUrl($barDomain);
        $this->getEntityManager()->persist($barShortUrl);

        $bazDomain = new Domain('baz.com');
        $this->getEntityManager()->persist($bazDomain);
        $bazShortUrl = $this->createShortUrl($bazDomain);
        $this->getEntityManager()->persist($bazShortUrl);

        $detachedDomain = new Domain('detached.com');
        $this->getEntityManager()->persist($detachedDomain);

        $this->getEntityManager()->flush();

        self::assertEquals([$barDomain, $bazDomain, $fooDomain], $this->repo->findDomainsWithout(null));
        self::assertEquals([$barDomain, $bazDomain], $this->repo->findDomainsWithout('foo.com'));
        self::assertEquals([$bazDomain, $fooDomain], $this->repo->findDomainsWithout('bar.com'));
        self::assertEquals([$barDomain, $fooDomain], $this->repo->findDomainsWithout('baz.com'));
    }

    private function createShortUrl(Domain $domain): ShortUrl
    {
        return new ShortUrl(
            'foo',
            ShortUrlMeta::fromRawData(['domain' => $domain->getAuthority()]),
            new class ($domain) implements ShortUrlRelationResolverInterface {
                private Domain $domain;

                public function __construct(Domain $domain)
                {
                    $this->domain = $domain;
                }

                public function resolveDomain(?string $domain): ?Domain
                {
                    return $this->domain;
                }

                public function resolveApiKey(?string $key): ?ApiKey
                {
                    return null;
                }
            },
        );
    }
}
