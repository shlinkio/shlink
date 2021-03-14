<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Domain\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepository;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

class DomainRepositoryTest extends DatabaseTestCase
{
    private DomainRepository $repo;

    protected function beforeEach(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Domain::class);
    }

    /** @test */
    public function findDomainsReturnsExpectedResult(): void
    {
        $fooDomain = new Domain('foo.com');
        $this->getEntityManager()->persist($fooDomain);
        $this->getEntityManager()->persist($this->createShortUrl($fooDomain));

        $barDomain = new Domain('bar.com');
        $this->getEntityManager()->persist($barDomain);
        $this->getEntityManager()->persist($this->createShortUrl($barDomain));

        $bazDomain = new Domain('baz.com');
        $this->getEntityManager()->persist($bazDomain);
        $this->getEntityManager()->persist($this->createShortUrl($bazDomain));

        $detachedDomain = new Domain('detached.com');
        $this->getEntityManager()->persist($detachedDomain);

        $this->getEntityManager()->flush();

        self::assertEquals([$barDomain, $bazDomain, $fooDomain], $this->repo->findDomainsWithout(null));
        self::assertEquals([$barDomain, $bazDomain], $this->repo->findDomainsWithout('foo.com'));
        self::assertEquals([$bazDomain, $fooDomain], $this->repo->findDomainsWithout('bar.com'));
        self::assertEquals([$barDomain, $fooDomain], $this->repo->findDomainsWithout('baz.com'));
    }

    /** @test */
    public function findDomainsReturnsJustThoseMatchingProvidedApiKey(): void
    {
        $authorApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($authorApiKey);
        $authorAndDomainApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($authorAndDomainApiKey);

        $fooDomain = new Domain('foo.com');
        $this->getEntityManager()->persist($fooDomain);
        $this->getEntityManager()->persist($this->createShortUrl($fooDomain, $authorApiKey));

        $barDomain = new Domain('bar.com');
        $this->getEntityManager()->persist($barDomain);
        $this->getEntityManager()->persist($this->createShortUrl($barDomain, $authorAndDomainApiKey));

        $bazDomain = new Domain('baz.com');
        $this->getEntityManager()->persist($bazDomain);
        $this->getEntityManager()->persist($this->createShortUrl($bazDomain, $authorApiKey));

        $this->getEntityManager()->flush();

        $authorAndDomainApiKey->registerRole(RoleDefinition::forDomain($fooDomain));

        $fooDomainApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forDomain($fooDomain)));
        $this->getEntityManager()->persist($fooDomainApiKey);

        $barDomainApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forDomain($barDomain)));
        $this->getEntityManager()->persist($fooDomainApiKey);

        $this->getEntityManager()->flush();

        self::assertEquals([$fooDomain], $this->repo->findDomainsWithout(null, $fooDomainApiKey));
        self::assertEquals([$barDomain], $this->repo->findDomainsWithout(null, $barDomainApiKey));
        self::assertEquals([$bazDomain, $fooDomain], $this->repo->findDomainsWithout(null, $authorApiKey));
        self::assertEquals([], $this->repo->findDomainsWithout(null, $authorAndDomainApiKey));
    }

    private function createShortUrl(Domain $domain, ?ApiKey $apiKey = null): ShortUrl
    {
        return ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['domain' => $domain->getAuthority(), 'apiKey' => $apiKey, 'longUrl' => 'foo']),
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

                public function resolveTags(array $tags): Collection
                {
                    return new ArrayCollection();
                }
            },
        );
    }
}
