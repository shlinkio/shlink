<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Domain\Resolver;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Domain\Resolver\SimpleDomainResolver;
use Shlinkio\Shlink\Core\Entity\Domain;

class SimpleDomainResolverTest extends TestCase
{
    /** @var SimpleDomainResolver */
    private $domainResolver;

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
            $this->assertNull($result);
        } else {
            $this->assertInstanceOf(Domain::class, $result);
            $this->assertEquals($domain, $result->getAuthority());
        }
    }

    public function provideDomains(): iterable
    {
        yield 'with empty domain' => [null];
        yield 'with non-empty domain' => ['domain.com'];
    }
}
