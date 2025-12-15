<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\ApiKey;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\ApiKey\RoleResolver;
use Shlinkio\Shlink\CLI\Command\Api\Input\ApiKeyInput;
use Shlinkio\Shlink\CLI\Exception\InvalidRoleConfigException;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;

class RoleResolverTest extends TestCase
{
    private RoleResolver $resolver;
    private MockObject & DomainServiceInterface $domainService;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(DomainServiceInterface::class);
        $this->resolver = new RoleResolver($this->domainService, new UrlShortenerOptions('default.com'));
    }

    #[Test, DataProvider('provideRoles')]
    public function properRolesAreResolvedBasedOnInput(
        ApiKeyInput $input,
        array $expectedRoles,
        int $expectedDomainCalls,
    ): void {
        $this->domainService->expects($this->exactly($expectedDomainCalls))->method('getOrCreate')->with(
            'example.com',
        )->willReturn(self::domainWithId(Domain::withAuthority('example.com')));

        $result = [...$this->resolver->determineRoles($input)];

        self::assertEquals($expectedRoles, $result);
    }

    public static function provideRoles(): iterable
    {
        $domain = self::domainWithId(Domain::withAuthority('example.com'));

        yield 'no roles' => [
            new ApiKeyInput(),
            [],
            0,
        ];
        yield 'domain role only' => [
            (function (): ApiKeyInput {
                $input = new ApiKeyInput();
                $input->domain = 'example.com';

                return $input;
            })(),
            [RoleDefinition::forDomain($domain)],
            1,
        ];
        yield 'author role only' => [
            (function (): ApiKeyInput {
                $input = new ApiKeyInput();
                $input->authorOnly = true;

                return $input;
            })(),
            [RoleDefinition::forAuthoredShortUrls()],
            0,
        ];
        yield 'all roles' => [
            (function (): ApiKeyInput {
                $input = new ApiKeyInput();
                $input->domain = 'example.com';
                $input->authorOnly = true;
                $input->noOrphanVisits = true;

                return $input;
            })(),
            [
                RoleDefinition::forAuthoredShortUrls(),
                RoleDefinition::forDomain($domain),
                RoleDefinition::forNoOrphanVisits(),
            ],
            1,
        ];
    }

    #[Test]
    public function exceptionIsThrownWhenTryingToAddDomainOnlyLinkedToDefaultDomain(): void
    {
        $input = new ApiKeyInput();
        $input->domain = 'default.com';

        $this->domainService->expects($this->never())->method('getOrCreate');

        $this->expectException(InvalidRoleConfigException::class);

        [...$this->resolver->determineRoles($input)];
    }

    private static function domainWithId(Domain $domain): Domain
    {
        $domain->setId('1');
        return $domain;
    }
}
