<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Api\DeleteKeyCommand;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Exception\ApiKeyNotFoundException;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteKeyCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & ApiKeyServiceInterface $apiKeyService;

    protected function setUp(): void
    {
        $this->apiKeyService = $this->createMock(ApiKeyServiceInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new DeleteKeyCommand($this->apiKeyService));
    }

    #[Test]
    public function warningIsReturnedIfNoArgumentIsProvidedInNonInteractiveMode(): void
    {
        $this->apiKeyService->expects($this->never())->method('deleteByName');
        $this->apiKeyService->expects($this->never())->method('listKeys');

        $exitCode = $this->commandTester->execute([], ['interactive' => false]);

        self::assertEquals(Command::INVALID, $exitCode);
    }

    #[Test]
    public function confirmationIsSkippedInNonInteractiveMode(): void
    {
        $this->apiKeyService->expects($this->once())->method('deleteByName');
        $this->apiKeyService->expects($this->never())->method('listKeys');

        $exitCode = $this->commandTester->execute(['name' => 'key to delete'], ['interactive' => false]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(Command::SUCCESS, $exitCode);
        self::assertStringNotContainsString('Are you sure you want to delete the API key?', $output);
    }

    #[Test]
    public function keyIsNotDeletedIfConfirmationIsCancelled(): void
    {
        $this->apiKeyService->expects($this->never())->method('deleteByName');
        $this->apiKeyService->expects($this->never())->method('listKeys');

        $this->commandTester->setInputs(['no']);
        $exitCode = $this->commandTester->execute(['name' => 'key_to_delete']);

        self::assertEquals(Command::INVALID, $exitCode);
    }

    #[Test]
    public function existingApiKeyNamesAreListedIfNoArgumentIsProvidedInInteractiveMode(): void
    {
        $name = 'the key to delete';
        $this->apiKeyService->expects($this->once())->method('deleteByName')->with($name);
        $this->apiKeyService->expects($this->once())->method('listKeys')->willReturn([
            ApiKey::fromMeta(ApiKeyMeta::fromParams(name: 'foo')),
            ApiKey::fromMeta(ApiKeyMeta::fromParams(name: $name)),
            ApiKey::fromMeta(ApiKeyMeta::fromParams(name: 'bar')),
        ]);

        $this->commandTester->setInputs([$name, 'y']);
        $exitCode = $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('What API key do you want to delete?', $output);
        self::assertStringContainsString('API key "the key to delete" properly deleted', $output);
        self::assertEquals(Command::SUCCESS, $exitCode);
    }

    #[Test]
    public function errorIsReturnedIfDisableByKeyThrowsException(): void
    {
        $apiKey = 'key to delete';
        $e = ApiKeyNotFoundException::forName($apiKey);
        $this->apiKeyService->expects($this->once())->method('deleteByName')->with($apiKey)->willThrowException($e);
        $this->apiKeyService->expects($this->never())->method('listKeys');

        $exitCode = $this->commandTester->execute(['name' => $apiKey]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString($e->getMessage(), $output);
        self::assertEquals(Command::FAILURE, $exitCode);
    }
}
