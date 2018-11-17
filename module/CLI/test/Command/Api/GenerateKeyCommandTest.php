<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use Cake\Chronos\Chronos;
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
        $create = $this->apiKeyService->create(null)->willReturn(new ApiKey());

        $this->commandTester->execute([
            'command' => 'api-key:generate',
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains('Generated API key: ', $output);
        $create->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function expirationDateIsDefinedIfProvided()
    {
        $this->apiKeyService->create(Argument::type(Chronos::class))->shouldBeCalledOnce()
                                                                    ->willReturn(new ApiKey());
        $this->commandTester->execute([
            'command' => 'api-key:generate',
            '--expirationDate' => '2016-01-01',
        ]);
    }
}
