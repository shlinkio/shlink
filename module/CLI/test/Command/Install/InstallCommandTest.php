<?php
namespace ShlinkioTest\Shlink\CLI\Command\Install;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Install\InstallCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Zend\Config\Writer\WriterInterface;

class InstallCommandTest extends TestCase
{
    /**
     * @var CommandTester
     */
    protected $commandTester;
    /**
     * @var ObjectProphecy
     */
    protected $configWriter;
    /**
     * @var ObjectProphecy
     */
    protected $filesystem;

    public function setUp()
    {
        $processMock = $this->prophesize(Process::class);
        $processMock->isSuccessful()->willReturn(true);
        $processHelper = $this->prophesize(ProcessHelper::class);
        $processHelper->getName()->willReturn('process');
        $processHelper->setHelperSet(Argument::any())->willReturn(null);
        $processHelper->run(Argument::cetera())->willReturn($processMock->reveal());

        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->filesystem->exists(Argument::cetera())->willReturn(false);

        $app = new Application();
        $helperSet = $app->getHelperSet();
        $helperSet->set($processHelper->reveal());
        $app->setHelperSet($helperSet);

        $this->configWriter = $this->prophesize(WriterInterface::class);
        $command = new InstallCommand($this->configWriter->reveal(), $this->filesystem->reveal());
        $app->add($command);

        $questionHelper = $command->getHelper('question');
        $questionHelper->setInputStream($this->createInputStream());
        $this->commandTester = new CommandTester($command);
    }

    protected function createInputStream()
    {
        $stream = fopen('php://memory', 'rb+', false);
        fwrite($stream, <<<CLI_INPUT

shlink_db
alejandro
1234


0
doma.in
abc123BCA

1
my_secret
CLI_INPUT
        );
        rewind($stream);

        return $stream;
    }

    /**
     * @test
     */
    public function inputIsProperlyParsed()
    {
        $this->configWriter->toFile(Argument::any(), [
            'app_options' => [
                'secret_key' => 'my_secret',
            ],
            'entity_manager' => [
                'connection' => [
                    'driver' => 'pdo_mysql',
                    'dbname' => 'shlink_db',
                    'user' => 'alejandro',
                    'password' => '1234',
                    'host' => 'localhost',
                    'port' => '3306',
                    'driverOptions' =>  [
                        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                    ]
                ],
            ],
            'translator' => [
                'locale' => 'en',
            ],
            'cli' => [
                'locale' => 'es',
            ],
            'url_shortener' => [
                'domain' => [
                    'schema' => 'http',
                    'hostname' => 'doma.in',
                ],
                'shortcode_chars' => 'abc123BCA',
            ],
        ], false)->shouldBeCalledTimes(1);

        $this->commandTester->execute([
            'command' => 'shlink:install',
        ]);
    }
}
