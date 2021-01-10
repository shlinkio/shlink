<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\ApiKey;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\ApiKey\RoleResolver;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Symfony\Component\Console\Input\InputInterface;

class RoleResolverTest extends TestCase
{
    use ProphecyTrait;

    private RoleResolver $resolver;
    private ObjectProphecy $domainService;

    protected function setUp(): void
    {
        $this->domainService = $this->prophesize(DomainServiceInterface::class);
        $this->resolver = new RoleResolver($this->domainService->reveal());
    }

    /**
     * @test
     * @dataProvider provideRoles
     */
    public function properRolesAreResolvedBasedOnInput(
        InputInterface $input,
        array $expectedRoles,
        int $expectedDomainCalls
    ): void {
        $getDomain = $this->domainService->getOrCreate('example.com')->willReturn(
            (new Domain('example.com'))->setId('1'),
        );

        $result = $this->resolver->determineRoles($input);

        self::assertEquals($expectedRoles, $result);
        $getDomain->shouldHaveBeenCalledTimes($expectedDomainCalls);
    }

    public function provideRoles(): iterable
    {
        $buildInput = function (array $definition): InputInterface {
            $input = $this->prophesize(InputInterface::class);

            foreach ($definition as $name => $value) {
                $input->getOption($name)->willReturn($value);
            }

            return $input->reveal();
        };

        yield 'no roles' => [
            $buildInput([RoleResolver::DOMAIN_ONLY_PARAM => null, RoleResolver::AUTHOR_ONLY_PARAM => false]),
            [],
            0,
        ];
        yield 'domain role only' => [
            $buildInput([RoleResolver::DOMAIN_ONLY_PARAM => 'example.com', RoleResolver::AUTHOR_ONLY_PARAM => false]),
            [RoleDefinition::forDomain('1')],
            1,
        ];
        yield 'author role only' => [
            $buildInput([RoleResolver::DOMAIN_ONLY_PARAM => null, RoleResolver::AUTHOR_ONLY_PARAM => true]),
            [RoleDefinition::forAuthoredShortUrls()],
            0,
        ];
        yield 'both roles' => [
            $buildInput([RoleResolver::DOMAIN_ONLY_PARAM => 'example.com', RoleResolver::AUTHOR_ONLY_PARAM => true]),
            [RoleDefinition::forAuthoredShortUrls(), RoleDefinition::forDomain('1')],
            1,
        ];
    }
}
