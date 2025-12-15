<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\ApiKey\RoleResolverInterface;
use Shlinkio\Shlink\CLI\Command\Api\GenerateKeyCommand;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateKeyCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & ApiKeyServiceInterface $apiKeyService;

    protected function setUp(): void
    {
        $this->apiKeyService = $this->createMock(ApiKeyServiceInterface::class);
        $roleResolver = $this->createStub(RoleResolverInterface::class);
        $roleResolver->method('determineRoles')->willReturn([]);

        $command = new GenerateKeyCommand($this->apiKeyService, $roleResolver);
        $this->commandTester = CliTestUtils::testerForCommand($command);
    }

    #[Test]
    public function noExpirationDateIsDefinedIfNotProvided(): void
    {
        $this->apiKeyService->expects($this->once())->method('create')->with(
            $this->callback(fn (ApiKeyMeta $meta) => $meta->expirationDate === null),
        )->willReturn(ApiKey::create());

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Generated API key: ', $output);
    }

    #[Test]
    public function expirationDateIsDefinedIfProvided(): void
    {
        $this->apiKeyService->expects($this->once())->method('create')->with(
            $this->callback(fn (ApiKeyMeta $meta) => $meta->expirationDate instanceof Chronos),
        )->willReturn(ApiKey::create());

        $this->commandTester->execute([
            '--expiration-date' => '2016-01-01',
        ]);
    }

    #[Test]
    public function nameIsDefinedIfProvided(): void
    {
        $this->apiKeyService->expects($this->once())->method('create')->with(
            $this->callback(fn (ApiKeyMeta $meta) => $meta->name === 'Alice'),
        )->willReturn(ApiKey::create());

        $exitCode = $this->commandTester->execute([
            '--name' => 'Alice',
        ]);

        self::assertEquals(Command::SUCCESS, $exitCode);
    }
}
