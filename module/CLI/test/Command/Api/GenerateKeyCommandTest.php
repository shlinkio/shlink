<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Api\GenerateKeyCommand;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateKeyCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private ObjectProphecy $apiKeyService;

    public function setUp(): void
    {
        $this->apiKeyService = $this->prophesize(ApiKeyServiceInterface::class);
        $command = new GenerateKeyCommand($this->apiKeyService->reveal());
        $app = new Application();
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function noExpirationDateIsDefinedIfNotProvided()
    {
        $create = $this->apiKeyService->create(null)->willReturn(new ApiKey());

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Generated API key: ', $output);
        $create->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function expirationDateIsDefinedIfProvided()
    {
        $this->apiKeyService->create(Argument::type(Chronos::class))->shouldBeCalledOnce()
                                                                    ->willReturn(new ApiKey());
        $this->commandTester->execute([
            '--expirationDate' => '2016-01-01',
        ]);
    }
}
