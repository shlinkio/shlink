<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Domain;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Domain\DomainService;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
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
        $this->domainService = new DomainService($this->em->reveal(), 'default.com');
    }

    /**
     * @test
     * @dataProvider provideExcludedDomains
     */
    public function listDomainsWithoutDelegatesIntoRepository(array $domains, array $expectedResult): void
    {
        $repo = $this->prophesize(DomainRepositoryInterface::class);
        $getRepo = $this->em->getRepository(Domain::class)->willReturn($repo->reveal());
        $findDomains = $repo->findDomainsWithout('default.com')->willReturn($domains);

        $result = $this->domainService->listDomainsWithout();

        self::assertEquals($expectedResult, $result);
        $getRepo->shouldHaveBeenCalledOnce();
        $findDomains->shouldHaveBeenCalledOnce();
    }

    public function provideExcludedDomains(): iterable
    {
        $default = new DomainItem('default.com', true);

        yield 'empty list' => [[], [$default]];
        yield 'one item' => [[new Domain('bar.com')], [$default, new DomainItem('bar.com', false)]];
        yield 'multiple items' => [
            [new Domain('foo.com'), new Domain('bar.com')],
            [$default, new DomainItem('foo.com', false), new DomainItem('bar.com', false)],
        ];
    }
}
