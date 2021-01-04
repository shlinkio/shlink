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
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;

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
    public function listDomainsDelegatesIntoRepository(array $domains, array $expectedResult): void
    {
        $repo = $this->prophesize(DomainRepositoryInterface::class);
        $getRepo = $this->em->getRepository(Domain::class)->willReturn($repo->reveal());
        $findDomains = $repo->findDomainsWithout('default.com', null)->willReturn($domains);

        $result = $this->domainService->listDomains();

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

    /** @test */
    public function getDomainThrowsExceptionWhenDomainIsNotFound(): void
    {
        $find = $this->em->find(Domain::class, '123')->willReturn(null);

        $this->expectException(DomainNotFoundException::class);
        $find->shouldBeCalledOnce();

        $this->domainService->getDomain('123');
    }

    /** @test */
    public function getDomainReturnsEntityWhenFound(): void
    {
        $domain = new Domain('');
        $find = $this->em->find(Domain::class, '123')->willReturn($domain);

        $result = $this->domainService->getDomain('123');

        self::assertSame($domain, $result);
        $find->shouldHaveBeenCalledOnce();
    }
}
