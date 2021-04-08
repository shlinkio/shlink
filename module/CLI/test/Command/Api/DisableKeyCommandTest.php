<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Api\DisableKeyCommand;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

class DisableKeyCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $apiKeyService;

    public function setUp(): void
    {
        $this->apiKeyService = $this->prophesize(ApiKeyServiceInterface::class);
        $this->commandTester = $this->testerForCommand(new DisableKeyCommand($this->apiKeyService->reveal()));
    }

    /** @test */
    public function providedApiKeyIsDisabled(): void
    {
        $apiKey = 'abcd1234';
        $this->apiKeyService->disable($apiKey)->shouldBeCalledOnce();

        $this->commandTester->execute([
            'apiKey' => $apiKey,
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('API key "abcd1234" properly disabled', $output);
    }

    /** @test */
    public function errorIsReturnedIfServiceThrowsException(): void
    {
        $apiKey = 'abcd1234';
        $expectedMessage = 'API key "abcd1234" does not exist.';
        $disable = $this->apiKeyService->disable($apiKey)->willThrow(new InvalidArgumentException($expectedMessage));

        $this->commandTester->execute([
            'apiKey' => $apiKey,
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString($expectedMessage, $output);
        $disable->shouldHaveBeenCalledOnce();
    }
}
