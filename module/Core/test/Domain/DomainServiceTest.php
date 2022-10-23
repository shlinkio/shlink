<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Domain;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\EmptyNotFoundRedirectConfig;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Domain\DomainService;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepositoryInterface;
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DomainServiceTest extends TestCase
{
    private DomainService $domainService;
    private MockObject $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->domainService = new DomainService($this->em, 'default.com');
    }

    /**
     * @test
     * @dataProvider provideExcludedDomains
     */
    public function listDomainsDelegatesIntoRepository(array $domains, array $expectedResult, ?ApiKey $apiKey): void
    {
        $repo = $this->createMock(DomainRepositoryInterface::class);
        $repo->expects($this->once())->method('findDomains')->with($apiKey)->willReturn($domains);
        $this->em->expects($this->once())->method('getRepository')->with(Domain::class)->willReturn($repo);

        $result = $this->domainService->listDomains($apiKey);

        self::assertEquals($expectedResult, $result);
    }

    public function provideExcludedDomains(): iterable
    {
        $default = DomainItem::forDefaultDomain('default.com', new EmptyNotFoundRedirectConfig());
        $adminApiKey = ApiKey::create();
        $domainSpecificApiKey = ApiKey::fromMeta(
            ApiKeyMeta::withRoles(RoleDefinition::forDomain(Domain::withAuthority('')->setId('123'))),
        );

        yield 'empty list without API key' => [[], [$default], null];
        yield 'one item without API key' => [
            [Domain::withAuthority('bar.com')],
            [$default, DomainItem::forNonDefaultDomain(Domain::withAuthority('bar.com'))],
            null,
        ];
        yield 'multiple items without API key' => [
            [Domain::withAuthority('foo.com'), Domain::withAuthority('bar.com')],
            [
                $default,
                DomainItem::forNonDefaultDomain(Domain::withAuthority('foo.com')),
                DomainItem::forNonDefaultDomain(Domain::withAuthority('bar.com')),
            ],
            null,
        ];

        yield 'empty list with admin API key' => [[], [$default], $adminApiKey];
        yield 'one item with admin API key' => [
            [Domain::withAuthority('bar.com')],
            [$default, DomainItem::forNonDefaultDomain(Domain::withAuthority('bar.com'))],
            $adminApiKey,
        ];
        yield 'multiple items with admin API key' => [
            [Domain::withAuthority('foo.com'), Domain::withAuthority('bar.com')],
            [
                $default,
                DomainItem::forNonDefaultDomain(Domain::withAuthority('foo.com')),
                DomainItem::forNonDefaultDomain(Domain::withAuthority('bar.com')),
            ],
            $adminApiKey,
        ];

        yield 'empty list with domain-specific API key' => [[], [], $domainSpecificApiKey];
        yield 'one item with domain-specific API key' => [
            [Domain::withAuthority('bar.com')],
            [DomainItem::forNonDefaultDomain(Domain::withAuthority('bar.com'))],
            $domainSpecificApiKey,
        ];
        yield 'multiple items with domain-specific API key' => [
            [Domain::withAuthority('foo.com'), Domain::withAuthority('bar.com')],
            [
                DomainItem::forNonDefaultDomain(Domain::withAuthority('foo.com')),
                DomainItem::forNonDefaultDomain(Domain::withAuthority('bar.com')),
            ],
            $domainSpecificApiKey,
        ];
    }

    /** @test */
    public function getDomainThrowsExceptionWhenDomainIsNotFound(): void
    {
        $this->em->expects($this->once())->method('find')->with(Domain::class, '123')->willReturn(null);

        $this->expectException(DomainNotFoundException::class);

        $this->domainService->getDomain('123');
    }

    /** @test */
    public function getDomainReturnsEntityWhenFound(): void
    {
        $domain = Domain::withAuthority('');
        $this->em->expects($this->once())->method('find')->with(Domain::class, '123')->willReturn($domain);

        $result = $this->domainService->getDomain('123');

        self::assertSame($domain, $result);
    }

    /**
     * @test
     * @dataProvider provideFoundDomains
     */
    public function getOrCreateAlwaysPersistsDomain(?Domain $foundDomain, ?ApiKey $apiKey): void
    {
        $authority = 'example.com';
        $repo = $this->createMock(DomainRepositoryInterface::class);
        $repo->method('findOneByAuthority')->with($authority, $apiKey)->willReturn(
            $foundDomain,
        );
        $this->em->expects($this->once())->method('getRepository')->with(Domain::class)->willReturn($repo);
        $this->em->expects($this->once())->method('persist')->with($foundDomain ?? $this->isInstanceOf(Domain::class));
        $this->em->expects($this->once())->method('flush');

        $result = $this->domainService->getOrCreate($authority, $apiKey);

        if ($foundDomain !== null) {
            self::assertSame($result, $foundDomain);
        }
    }

    /** @test */
    public function getOrCreateThrowsExceptionForApiKeysWithDomainRole(): void
    {
        $authority = 'example.com';
        $domain = Domain::withAuthority($authority)->setId('1');
        $apiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forDomain($domain)));
        $repo = $this->createMock(DomainRepositoryInterface::class);
        $repo->method('findOneByAuthority')->with($authority, $apiKey)->willReturn(null);
        $this->em->expects($this->once())->method('getRepository')->with(Domain::class)->willReturn($repo);
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $this->expectException(DomainNotFoundException::class);

        $this->domainService->getOrCreate($authority, $apiKey);
    }

    /**
     * @test
     * @dataProvider provideFoundDomains
     */
    public function configureNotFoundRedirectsConfiguresFetchedDomain(?Domain $foundDomain, ?ApiKey $apiKey): void
    {
        $authority = 'example.com';
        $repo = $this->createMock(DomainRepositoryInterface::class);
        $repo->method('findOneByAuthority')->with($authority, $apiKey)->willReturn($foundDomain);
        $this->em->expects($this->once())->method('getRepository')->with(Domain::class)->willReturn($repo);
        $this->em->expects($this->once())->method('persist')->with($foundDomain ?? $this->isInstanceOf(Domain::class));
        $this->em->expects($this->once())->method('flush');

        $result = $this->domainService->configureNotFoundRedirects($authority, NotFoundRedirects::withRedirects(
            'foo.com',
            'bar.com',
            'baz.com',
        ), $apiKey);

        if ($foundDomain !== null) {
            self::assertSame($result, $foundDomain);
        }
        self::assertEquals('foo.com', $result->baseUrlRedirect());
        self::assertEquals('bar.com', $result->regular404Redirect());
        self::assertEquals('baz.com', $result->invalidShortUrlRedirect());
    }

    public function provideFoundDomains(): iterable
    {
        $domain = Domain::withAuthority('');
        $adminApiKey = ApiKey::create();
        $authorApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));

        yield 'domain not found and no API key' => [null, null];
        yield 'domain found and no API key' => [$domain, null];
        yield 'domain not found and admin API key' => [null, $adminApiKey];
        yield 'domain found and admin API key' => [$domain, $adminApiKey];
        yield 'domain not found and author API key' => [null, $authorApiKey];
        yield 'domain found and author API key' => [$domain, $authorApiKey];
    }
}
