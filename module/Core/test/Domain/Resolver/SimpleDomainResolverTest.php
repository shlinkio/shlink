<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Domain\Resolver;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Domain\Resolver\SimpleDomainResolver;
use Shlinkio\Shlink\Core\Entity\Domain;

class SimpleDomainResolverTest extends TestCase
{
    private SimpleDomainResolver $domainResolver;

    public function setUp(): void
    {
        $this->domainResolver = new SimpleDomainResolver();
    }

    /**
     * @test
     * @dataProvider provideDomains
     */
    public function resolvesExpectedDomain(?string $domain): void
    {
        $result = $this->domainResolver->resolveDomain($domain);

        if ($domain === null) {
            self::assertNull($result);
        } else {
            self::assertInstanceOf(Domain::class, $result);
            self::assertEquals($domain, $result->getAuthority());
        }
    }

    public function provideDomains(): iterable
    {
        yield 'with empty domain' => [null];
        yield 'with non-empty domain' => ['domain.com'];
    }
}
