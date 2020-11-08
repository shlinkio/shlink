<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Domain;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Domain\DomainService;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepositoryInterface;
use Shlinkio\Shlink\Core\Entity\Domain;

class DomainServiceTest extends TestCase
{
    use ProphecyTrait;

    private DomainService $domainService;
    private ObjectProphecy $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->domainService = new DomainService($this->em->reveal());
    }

    /**
     * @test
     * @dataProvider provideExcludedDomains
     */
    public function listDomainsWithoutDelegatesIntoRepository(?string $excludedDomain, array $expectedResult): void
    {
        $repo = $this->prophesize(DomainRepositoryInterface::class);
        $getRepo = $this->em->getRepository(Domain::class)->willReturn($repo->reveal());
        $findDomains = $repo->findDomainsWithout($excludedDomain)->willReturn($expectedResult);

        $result = $this->domainService->listDomainsWithout($excludedDomain);

        self::assertEquals($expectedResult, $result);
        $getRepo->shouldHaveBeenCalledOnce();
        $findDomains->shouldHaveBeenCalledOnce();
    }

    public function provideExcludedDomains(): iterable
    {
        yield 'no excluded domain' => [null, []];
        yield 'foo.com excluded domain' => ['foo.com', []];
        yield 'bar.com excluded domain' => ['bar.com', [new Domain('bar.com')]];
        yield 'baz.com excluded domain' => ['baz.com', [new Domain('foo.com'), new Domain('bar.com')]];
    }
}
