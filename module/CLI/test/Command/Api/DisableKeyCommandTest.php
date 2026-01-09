<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Api\DisableKeyCommand;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DisableKeyCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & ApiKeyServiceInterface $apiKeyService;

    protected function setUp(): void
    {
        $this->apiKeyService = $this->createMock(ApiKeyServiceInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new DisableKeyCommand($this->apiKeyService));
    }

    #[Test]
    public function providedApiKeyIsDisabled(): void
    {
        $apiKey = 'abcd1234';
        $this->apiKeyService->expects($this->once())->method('disableByName')->with($apiKey);

        $exitCode = $this->commandTester->execute(['name' => $apiKey]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('API key "abcd1234" properly disabled', $output);
        self::assertEquals(Command::SUCCESS, $exitCode);
    }

    #[Test]
    public function errorIsReturnedIfDisableByNameThrowsException(): void
    {
        $apiKey = 'abcd1234';
        $expectedMessage = 'API key "abcd1234" does not exist.';
        $this->apiKeyService->expects($this->once())->method('disableByName')->with($apiKey)->willThrowException(
            new InvalidArgumentException($expectedMessage),
        );

        $exitCode = $this->commandTester->execute(['name' => $apiKey]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString($expectedMessage, $output);
        self::assertEquals(Command::FAILURE, $exitCode);
    }

    #[Test]
    public function warningIsReturnedIfNoArgumentIsProvidedInNonInteractiveMode(): void
    {
        $this->apiKeyService->expects($this->never())->method('disableByName');
        $this->apiKeyService->expects($this->never())->method('listKeys');

        $exitCode = $this->commandTester->execute([], ['interactive' => false]);

        self::assertEquals(Command::INVALID, $exitCode);
    }

    #[Test]
    public function existingApiKeyNamesAreListedIfNoArgumentIsProvidedInInteractiveMode(): void
    {
        $name = 'the key to delete';
        $this->apiKeyService->expects($this->once())->method('disableByName')->with($name);
        $this->apiKeyService->expects($this->once())->method('listKeys')->willReturn([
            ApiKey::fromMeta(ApiKeyMeta::fromParams(name: 'foo')),
            ApiKey::fromMeta(ApiKeyMeta::fromParams(name: $name)),
            ApiKey::fromMeta(ApiKeyMeta::fromParams(name: 'bar')),
        ]);

        $this->commandTester->setInputs([$name]);
        $exitCode = $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('API key "the key to delete" properly disabled', $output);
        self::assertEquals(Command::SUCCESS, $exitCode);
    }
}
