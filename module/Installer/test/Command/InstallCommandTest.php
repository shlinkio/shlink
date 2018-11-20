<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Installer\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionObject;
use Shlinkio\Shlink\Installer\Command\InstallCommand;
use Shlinkio\Shlink\Installer\Config\ConfigCustomizerManagerInterface;
use Shlinkio\Shlink\Installer\Config\Plugin\ConfigCustomizerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Zend\Config\Writer\WriterInterface;

class InstallCommandTest extends TestCase
{
    /** @var InstallCommand */
    protected $command;
    /** @var CommandTester */
    protected $commandTester;
    /** @var ObjectProphecy */
    protected $configWriter;
    /** @var ObjectProphecy */
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

        $this->configWriter = $this->prophesize(WriterInterface::class);

        $configCustomizer = $this->prophesize(ConfigCustomizerInterface::class);
        $configCustomizers = $this->prophesize(ConfigCustomizerManagerInterface::class);
        $configCustomizers->get(Argument::cetera())->willReturn($configCustomizer->reveal());

        $finder = $this->prophesize(PhpExecutableFinder::class);
        $finder->find(false)->willReturn('php');

        $app = new Application();
        $helperSet = $app->getHelperSet();
        $helperSet->set($processHelper->reveal());
        $app->setHelperSet($helperSet);
        $this->command = new InstallCommand(
            $this->configWriter->reveal(),
            $this->filesystem->reveal(),
            $configCustomizers->reveal(),
            false,
            $finder->reveal()
        );
        $app->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @test
     */
    public function generatedConfigIsProperlyPersisted()
    {
        $this->configWriter->toFile(Argument::any(), Argument::type('array'), false)->shouldBeCalledOnce();
        $this->commandTester->execute([]);
    }

    /**
     * @test
     */
    public function cachedConfigIsDeletedIfExists()
    {
        /** @var MethodProphecy $appConfigExists */
        $appConfigExists = $this->filesystem->exists('data/cache/app_config.php')->willReturn(true);
        /** @var MethodProphecy $appConfigRemove */
        $appConfigRemove = $this->filesystem->remove('data/cache/app_config.php')->willReturn(null);

        $this->commandTester->execute([]);

        $appConfigExists->shouldHaveBeenCalledOnce();
        $appConfigRemove->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function exceptionWhileDeletingCachedConfigCancelsProcess()
    {
        /** @var MethodProphecy $appConfigExists */
        $appConfigExists = $this->filesystem->exists('data/cache/app_config.php')->willReturn(true);
        /** @var MethodProphecy $appConfigRemove */
        $appConfigRemove = $this->filesystem->remove('data/cache/app_config.php')->willThrow(IOException::class);
        /** @var MethodProphecy $configToFile */
        $configToFile = $this->configWriter->toFile(Argument::cetera())->willReturn(true);

        $this->commandTester->execute([]);

        $appConfigExists->shouldHaveBeenCalledOnce();
        $appConfigRemove->shouldHaveBeenCalledOnce();
        $configToFile->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function whenCommandIsUpdatePreviousConfigCanBeImported()
    {
        $ref = new ReflectionObject($this->command);
        $prop = $ref->getProperty('isUpdate');
        $prop->setAccessible(true);
        $prop->setValue($this->command, true);

        /** @var MethodProphecy $importedConfigExists */
        $importedConfigExists = $this->filesystem->exists(
            __DIR__ . '/../../test-resources/' . InstallCommand::GENERATED_CONFIG_PATH
        )->willReturn(true);

        $this->commandTester->setInputs([
            '',
            '/foo/bar/wrong_previous_shlink',
            '',
            __DIR__ . '/../../test-resources',
        ]);
        $this->commandTester->execute([]);

        $importedConfigExists->shouldHaveBeenCalled();
    }
}
