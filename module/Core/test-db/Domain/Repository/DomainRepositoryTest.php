<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Domain\Repository;

use Shlinkio\Shlink\Core\Domain\Repository\DomainRepository;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

class DomainRepositoryTest extends DatabaseTestCase
{
    protected const ENTITIES_TO_EMPTY = [Domain::class];

    private DomainRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Domain::class);
    }

    /** @test */
    public function findDomainsReturnsExpectedResult(): void
    {
        $fooDomain = new Domain('foo.com');
        $barDomain = new Domain('bar.com');
        $bazDomain = new Domain('baz.com');

        $this->getEntityManager()->persist($fooDomain);
        $this->getEntityManager()->persist($barDomain);
        $this->getEntityManager()->persist($bazDomain);
        $this->getEntityManager()->flush();

        self::assertEquals([$barDomain, $bazDomain, $fooDomain], $this->repo->findDomainsWithout());
        self::assertEquals([$barDomain, $bazDomain], $this->repo->findDomainsWithout('foo.com'));
        self::assertEquals([$bazDomain, $fooDomain], $this->repo->findDomainsWithout('bar.com'));
        self::assertEquals([$barDomain, $fooDomain], $this->repo->findDomainsWithout('baz.com'));
    }
}
