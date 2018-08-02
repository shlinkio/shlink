<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Api\GenerateKeyCommand;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;

class GenerateKeyCommandTest extends TestCase
{
    /**
     * @var CommandTester
     */
    protected $commandTester;
    /**
     * @var ObjectProphecy
     */
    protected $apiKeyService;

    public function setUp()
    {
        $this->apiKeyService = $this->prophesize(ApiKeyService::class);
        $command = new GenerateKeyCommand($this->apiKeyService->reveal(), Translator::factory([]));
        $app = new Application();
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function noExpirationDateIsDefinedIfNotProvided()
    {
        $this->apiKeyService->create(null)->shouldBeCalledTimes(1)
                                          ->willReturn(new ApiKey());
        $this->commandTester->execute([
            'command' => 'api-key:generate',
        ]);
    }

    /**
     * @test
     */
    public function expirationDateIsDefinedIfProvided()
    {
        $this->apiKeyService->create(Argument::type(\DateTime::class))->shouldBeCalledTimes(1)
                                                                      ->willReturn(new ApiKey());
        $this->commandTester->execute([
            'command' => 'api-key:generate',
            '--expirationDate' => '2016-01-01',
        ]);
    }
}
