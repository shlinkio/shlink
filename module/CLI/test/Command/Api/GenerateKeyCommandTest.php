<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\ApiKey\RoleResolverInterface;
use Shlinkio\Shlink\CLI\Command\Api\GenerateKeyCommand;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateKeyCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $apiKeyService;

    public function setUp(): void
    {
        $this->apiKeyService = $this->prophesize(ApiKeyServiceInterface::class);
        $roleResolver = $this->prophesize(RoleResolverInterface::class);
        $roleResolver->determineRoles(Argument::type(InputInterface::class))->willReturn([]);

        $command = new GenerateKeyCommand($this->apiKeyService->reveal(), $roleResolver->reveal());
        $this->commandTester = $this->testerForCommand($command);
    }

    /** @test */
    public function noExpirationDateIsDefinedIfNotProvided(): void
    {
        $this->apiKeyService->create(null, null)->shouldBeCalledOnce()->willReturn(ApiKey::create());

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Generated API key: ', $output);
    }

    /** @test */
    public function expirationDateIsDefinedIfProvided(): void
    {
        $this->apiKeyService->create(Argument::type(Chronos::class), null)->shouldBeCalledOnce()->willReturn(
            ApiKey::create(),
        );

        $this->commandTester->execute([
            '--expiration-date' => '2016-01-01',
        ]);
    }

    /** @test */
    public function nameIsDefinedIfProvided(): void
    {
        $this->apiKeyService->create(null, Argument::type('string'))->shouldBeCalledOnce()->willReturn(
            ApiKey::create(),
        );

        $this->commandTester->execute([
            '--name' => 'Alice',
        ]);
    }
}
