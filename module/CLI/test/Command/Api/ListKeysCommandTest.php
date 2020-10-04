<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Api\ListKeysCommand;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ListKeysCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private ObjectProphecy $apiKeyService;

    public function setUp(): void
    {
        $this->apiKeyService = $this->prophesize(ApiKeyServiceInterface::class);
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

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Key', $output);
        self::assertStringContainsString('Is enabled', $output);
        self::assertStringContainsString(' +++ ', $output);
        self::assertStringNotContainsString(' --- ', $output);
        self::assertStringContainsString('Expiration date', $output);
    }

    /** @test */
    public function onlyEnabledKeysAreListedIfEnabledOnlyIsProvided(): void
    {
        $this->apiKeyService->listKeys(true)->willReturn([
            (new ApiKey())->disable(),
            new ApiKey(),
        ])->shouldBeCalledOnce();

        $this->commandTester->execute([
            '--enabledOnly' => true,
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Key', $output);
        self::assertStringNotContainsString('Is enabled', $output);
        self::assertStringNotContainsString(' +++ ', $output);
        self::assertStringNotContainsString(' --- ', $output);
        self::assertStringContainsString('Expiration date', $output);
    }
}
