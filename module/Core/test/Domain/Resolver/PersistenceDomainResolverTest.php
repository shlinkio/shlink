<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Domain\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Domain\Resolver\PersistenceDomainResolver;
use Shlinkio\Shlink\Core\Entity\Domain;

class PersistenceDomainResolverTest extends TestCase
{
    private PersistenceDomainResolver $domainResolver;
    private ObjectProphecy $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->domainResolver = new PersistenceDomainResolver($this->em->reveal());
    }

    /** @test */
    public function returnsEmptyWhenNoDomainIsProvided(): void
    {
        $getRepository = $this->em->getRepository(Domain::class);

        $this->assertNull($this->domainResolver->resolveDomain(null));
        $getRepository->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideFoundDomains
     */
    public function findsOrCreatesDomainWhenValueIsProvided(?Domain $foundDomain, string $authority): void
    {
        $repo = $this->prophesize(ObjectRepository::class);
        $findDomain = $repo->findOneBy(['authority' => $authority])->willReturn($foundDomain);
        $getRepository = $this->em->getRepository(Domain::class)->willReturn($repo->reveal());

        $result = $this->domainResolver->resolveDomain($authority);

        if ($foundDomain !== null) {
            $this->assertSame($result, $foundDomain);
        }
        $this->assertInstanceOf(Domain::class, $result);
        $this->assertEquals($authority, $result->getAuthority());
        $findDomain->shouldHaveBeenCalledOnce();
        $getRepository->shouldHaveBeenCalledOnce();
    }

    public function provideFoundDomains(): iterable
    {
        $authority = 'doma.in';

        yield 'without found domain' => [null, $authority];
        yield 'with found domain' => [new Domain($authority), $authority];
    }
}
