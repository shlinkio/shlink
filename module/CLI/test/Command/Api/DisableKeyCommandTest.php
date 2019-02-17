<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Api\DisableKeyCommand;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DisableKeyCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $apiKeyService;

    public function setUp(): void
    {
        $this->apiKeyService = $this->prophesize(ApiKeyService::class);
        $command = new DisableKeyCommand($this->apiKeyService->reveal());
        $app = new Application();
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function providedApiKeyIsDisabled()
    {
        $apiKey = 'abcd1234';
        $this->apiKeyService->disable($apiKey)->shouldBeCalledOnce();

        $this->commandTester->execute([
            'command' => 'api-key:disable',
            'apiKey' => $apiKey,
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('API key "abcd1234" properly disabled', $output);
    }

    /** @test */
    public function errorIsReturnedIfServiceThrowsException()
    {
        $apiKey = 'abcd1234';
        $disable = $this->apiKeyService->disable($apiKey)->willThrow(InvalidArgumentException::class);

        $this->commandTester->execute([
            'command' => 'api-key:disable',
            'apiKey' => $apiKey,
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('API key "abcd1234" does not exist.', $output);
        $disable->shouldHaveBeenCalledOnce();
    }
}
