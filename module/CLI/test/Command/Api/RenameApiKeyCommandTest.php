<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Api\RenameApiKeyCommand;
use Shlinkio\Shlink\Core\Model\Renaming;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

class RenameApiKeyCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & ApiKeyServiceInterface $apiKeyService;

    protected function setUp(): void
    {
        $this->apiKeyService = $this->createMock(ApiKeyServiceInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new RenameApiKeyCommand($this->apiKeyService));
    }

    #[Test]
    public function oldNameIsRequestedIfNotProvided(): void
    {
        $oldName = 'old name';
        $newName = 'new name';

        $this->apiKeyService->expects($this->once())->method('listKeys')->willReturn([
            ApiKey::fromMeta(ApiKeyMeta::fromParams(name: 'foo')),
            ApiKey::fromMeta(ApiKeyMeta::fromParams(name: $oldName)),
            ApiKey::fromMeta(ApiKeyMeta::fromParams(name: 'bar')),
        ]);
        $this->apiKeyService->expects($this->once())->method('renameApiKey')->with(
            Renaming::fromNames($oldName, $newName),
        );

        $this->commandTester->setInputs([$oldName]);
        $this->commandTester->execute([
            'newName' => $newName,
        ]);
    }

    #[Test]
    public function newNameIsRequestedIfNotProvided(): void
    {
        $oldName = 'old name';
        $newName = 'new name';

        $this->apiKeyService->expects($this->never())->method('listKeys');
        $this->apiKeyService->expects($this->once())->method('renameApiKey')->with(
            Renaming::fromNames($oldName, $newName),
        );

        $this->commandTester->setInputs([$newName]);
        $this->commandTester->execute([
            'oldName' => $oldName,
        ]);
    }

    #[Test]
    public function apiIsRenamedWithProvidedNames(): void
    {
        $oldName = 'old name';
        $newName = 'new name';

        $this->apiKeyService->expects($this->never())->method('listKeys');
        $this->apiKeyService->expects($this->once())->method('renameApiKey')->with(
            Renaming::fromNames($oldName, $newName),
        );

        $this->commandTester->execute([
            'oldName' => $oldName,
            'newName' => $newName,
        ]);
    }
}
