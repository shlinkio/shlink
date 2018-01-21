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
use Zend\I18n\Translator\Translator;

class DisableKeyCommandTest extends TestCase
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
        $command = new DisableKeyCommand($this->apiKeyService->reveal(), Translator::factory([]));
        $app = new Application();
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function providedApiKeyIsDisabled()
    {
        $apiKey = 'abcd1234';
        $this->apiKeyService->disable($apiKey)->shouldBeCalledTimes(1);
        $this->commandTester->execute([
            'command' => 'api-key:disable',
            'apiKey' => $apiKey,
        ]);
    }

    /**
     * @test
     */
    public function errorIsReturnedIfServiceThrowsException()
    {
        $apiKey = 'abcd1234';
        $this->apiKeyService->disable($apiKey)->willThrow(InvalidArgumentException::class)
                                              ->shouldBeCalledTimes(1);

        $this->commandTester->execute([
            'command' => 'api-key:disable',
            'apiKey' => $apiKey,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertContains('API key "abcd1234" does not exist.', $output);
    }
}
