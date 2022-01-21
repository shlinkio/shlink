<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Domain\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
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

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Domain::class);
    }

    /** @test */
    public function expectedDomainsAreFoundWhenNoApiKeyIsInvolved(): void
    {
        $fooDomain = Domain::withAuthority('foo.com');
        $this->getEntityManager()->persist($fooDomain);
        $this->getEntityManager()->persist($this->createShortUrl($fooDomain));

        $barDomain = Domain::withAuthority('bar.com');
        $this->getEntityManager()->persist($barDomain);
        $this->getEntityManager()->persist($this->createShortUrl($barDomain));

        $bazDomain = Domain::withAuthority('baz.com');
        $this->getEntityManager()->persist($bazDomain);
        $this->getEntityManager()->persist($this->createShortUrl($bazDomain));

        $detachedDomain = Domain::withAuthority('detached.com');
        $this->getEntityManager()->persist($detachedDomain);

        $detachedWithRedirects = Domain::withAuthority('detached-with-redirects.com');
        $detachedWithRedirects->configureNotFoundRedirects(NotFoundRedirects::withRedirects('foo.com', 'bar.com'));
        $this->getEntityManager()->persist($detachedWithRedirects);

        $this->getEntityManager()->flush();

        self::assertEquals([$barDomain, $bazDomain, $detachedWithRedirects, $fooDomain], $this->repo->findDomains());
        self::assertEquals($barDomain, $this->repo->findOneByAuthority('bar.com'));
        self::assertEquals($detachedWithRedirects, $this->repo->findOneByAuthority('detached-with-redirects.com'));
        self::assertNull($this->repo->findOneByAuthority('does-not-exist.com'));
        self::assertEquals($detachedDomain, $this->repo->findOneByAuthority('detached.com'));
    }

    /** @test */
    public function expectedDomainsAreFoundWhenApiKeyIsProvided(): void
    {
        $authorApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($authorApiKey);
        $authorAndDomainApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($authorAndDomainApiKey);

        $fooDomain = Domain::withAuthority('foo.com');
        $this->getEntityManager()->persist($fooDomain);
        $this->getEntityManager()->persist($this->createShortUrl($fooDomain, $authorApiKey));

        $barDomain = Domain::withAuthority('bar.com');
        $this->getEntityManager()->persist($barDomain);
        $this->getEntityManager()->persist($this->createShortUrl($barDomain, $authorAndDomainApiKey));

        $bazDomain = Domain::withAuthority('baz.com');
        $this->getEntityManager()->persist($bazDomain);
        $this->getEntityManager()->persist($this->createShortUrl($bazDomain, $authorApiKey));

        $detachedDomain = Domain::withAuthority('detached.com');
        $this->getEntityManager()->persist($detachedDomain);

        $detachedWithRedirects = Domain::withAuthority('detached-with-redirects.com');
        $detachedWithRedirects->configureNotFoundRedirects(NotFoundRedirects::withRedirects('foo.com', 'bar.com'));
        $this->getEntityManager()->persist($detachedWithRedirects);

        $this->getEntityManager()->flush();

        $authorAndDomainApiKey->registerRole(RoleDefinition::forDomain($fooDomain));

        $fooDomainApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forDomain($fooDomain)));
        $this->getEntityManager()->persist($fooDomainApiKey);

        $barDomainApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forDomain($barDomain)));
        $this->getEntityManager()->persist($barDomainApiKey);

        $detachedWithRedirectsApiKey = ApiKey::fromMeta(
            ApiKeyMeta::withRoles(RoleDefinition::forDomain($detachedWithRedirects)),
        );
        $this->getEntityManager()->persist($detachedWithRedirectsApiKey);

        $this->getEntityManager()->flush();

        self::assertEquals([$fooDomain], $this->repo->findDomains($fooDomainApiKey));
        self::assertEquals([$barDomain], $this->repo->findDomains($barDomainApiKey));
        self::assertEquals([$detachedWithRedirects], $this->repo->findDomains($detachedWithRedirectsApiKey));
        self::assertEquals([$bazDomain, $fooDomain], $this->repo->findDomains($authorApiKey));
        self::assertEquals([], $this->repo->findDomains($authorAndDomainApiKey));

        self::assertEquals($fooDomain, $this->repo->findOneByAuthority('foo.com', $authorApiKey));
        self::assertNull($this->repo->findOneByAuthority('bar.com', $authorApiKey));
        self::assertEquals($barDomain, $this->repo->findOneByAuthority('bar.com', $barDomainApiKey));
        self::assertEquals(
            $detachedWithRedirects,
            $this->repo->findOneByAuthority('detached-with-redirects.com', $detachedWithRedirectsApiKey),
        );
        self::assertNull($this->repo->findOneByAuthority('foo.com', $detachedWithRedirectsApiKey));
    }

    private function createShortUrl(Domain $domain, ?ApiKey $apiKey = null): ShortUrl
    {
        return ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['domain' => $domain->getAuthority(), 'apiKey' => $apiKey, 'longUrl' => 'foo']),
            new class ($domain) implements ShortUrlRelationResolverInterface {
                public function __construct(private Domain $domain)
                {
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
