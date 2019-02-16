<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Api\ListKeysCommand;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ListKeysCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $apiKeyService;

    public function setUp(): void
    {
        $this->apiKeyService = $this->prophesize(ApiKeyService::class);
        $command = new ListKeysCommand($this->apiKeyService->reveal());
        $app = new Application();
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function everythingIsListedIfEnabledOnlyIsNotProvided(): void
    {
        $this->apiKeyService->listKeys(false)->willReturn([
            new ApiKey(),
            new ApiKey(),
            new ApiKey(),
        ])->shouldBeCalledOnce();

        $this->commandTester->execute([
            'command' => ListKeysCommand::NAME,
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Key', $output);
        $this->assertStringContainsString('Is enabled', $output);
        $this->assertStringContainsString(' +++ ', $output);
        $this->assertStringNotContainsString(' --- ', $output);
        $this->assertStringContainsString('Expiration date', $output);
    }

    /** @test */
    public function onlyEnabledKeysAreListedIfEnabledOnlyIsProvided(): void
    {
        $this->apiKeyService->listKeys(true)->willReturn([
            (new ApiKey())->disable(),
            new ApiKey(),
        ])->shouldBeCalledOnce();

        $this->commandTester->execute([
            'command' => ListKeysCommand::NAME,
            '--enabledOnly' => true,
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Key', $output);
        $this->assertStringNotContainsString('Is enabled', $output);
        $this->assertStringNotContainsString(' +++ ', $output);
        $this->assertStringNotContainsString(' --- ', $output);
        $this->assertStringContainsString('Expiration date', $output);
    }
}
