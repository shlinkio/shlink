<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
    private MockObject & ApiKeyServiceInterface $apiKeyService;

    protected function setUp(): void
    {
        $this->apiKeyService = $this->createMock(ApiKeyServiceInterface::class);
        $roleResolver = $this->createMock(RoleResolverInterface::class);
        $roleResolver->method('determineRoles')->with($this->isInstanceOf(InputInterface::class))->willReturn([]);

        $command = new GenerateKeyCommand($this->apiKeyService, $roleResolver);
        $this->commandTester = $this->testerForCommand($command);
    }

    #[Test]
    public function noExpirationDateIsDefinedIfNotProvided(): void
    {
        $this->apiKeyService->expects($this->once())->method('create')->with(
            $this->isNull(),
            $this->isNull(),
        )->willReturn(ApiKey::create());

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Generated API key: ', $output);
    }

    #[Test]
    public function expirationDateIsDefinedIfProvided(): void
    {
        $this->apiKeyService->expects($this->once())->method('create')->with(
            $this->isInstanceOf(Chronos::class),
            $this->isNull(),
        )->willReturn(ApiKey::create());

        $this->commandTester->execute([
            '--expiration-date' => '2016-01-01',
        ]);
    }

    #[Test]
    public function nameIsDefinedIfProvided(): void
    {
        $this->apiKeyService->expects($this->once())->method('create')->with(
            $this->isNull(),
            $this->isType('string'),
        )->willReturn(ApiKey::create());

        $this->commandTester->execute([
            '--name' => 'Alice',
        ]);
    }
}
