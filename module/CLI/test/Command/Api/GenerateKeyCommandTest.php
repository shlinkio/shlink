<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\ApiKey\RoleResolverInterface;
use Shlinkio\Shlink\CLI\Command\Api\GenerateKeyCommand;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateKeyCommandTest extends TestCase
{
    use ProphecyTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $apiKeyService;
    private ObjectProphecy $roleResolver;

    public function setUp(): void
    {
        $this->apiKeyService = $this->prophesize(ApiKeyServiceInterface::class);
        $this->roleResolver = $this->prophesize(RoleResolverInterface::class);
        $this->roleResolver->determineRoles(Argument::type(InputInterface::class))->willReturn([]);

        $command = new GenerateKeyCommand($this->apiKeyService->reveal(), $this->roleResolver->reveal());
        $app = new Application();
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function noExpirationDateIsDefinedIfNotProvided(): void
    {
        $this->apiKeyService->create(
            null,   // Expiration date
            null,    // Name
        )->shouldBeCalledOnce()
        ->willReturn(new ApiKey());

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Generated API key: ', $output);
    }

    /** @test */
    public function expirationDateIsDefinedIfProvided(): void
    {
        $this->apiKeyService->create(
            Argument::type(Chronos::class), // Expiration date
            null,    // Name
        )->shouldBeCalledOnce()
        ->willReturn(new ApiKey());

        $this->commandTester->execute([
            '--expiration-date' => '2016-01-01',
        ]);
    }

    /** @test */
    public function nameIsDefinedIfProvided(): void
    {
        $this->apiKeyService->create(
            null,   // Expiration date
            Argument::type('string'),    // Name
        )->shouldBeCalledOnce()
        ->willReturn(new ApiKey());

        $this->commandTester->execute([
            '--name' => 'Alice',
        ]);
    }
}
