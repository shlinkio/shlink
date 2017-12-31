<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Install;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shlinkio\Shlink\CLI\Install\ConfigCustomizerManagerInterface;
use Shlinkio\Shlink\CLI\Install\Plugin;
use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Config\Writer\WriterInterface;

class InstallCommand extends Command
{
    const GENERATED_CONFIG_PATH = 'config/params/generated_config.php';

    /**
     * @var SymfonyStyle
     */
    private $io;
    /**
     * @var ProcessHelper
     */
    private $processHelper;
    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var ConfigCustomizerManagerInterface
     */
    private $configCustomizers;
    /**
     * @var bool
     */
    private $isUpdate;

    /**
     * InstallCommand constructor.
     * @param WriterInterface $configWriter
     * @param Filesystem $filesystem
     * @param ConfigCustomizerManagerInterface $configCustomizers
     * @param bool $isUpdate
     * @throws LogicException
     */
    public function __construct(
        WriterInterface $configWriter,
        Filesystem $filesystem,
        ConfigCustomizerManagerInterface $configCustomizers,
        $isUpdate = false
    ) {
        parent::__construct();
        $this->configWriter = $configWriter;
        $this->isUpdate = $isUpdate;
        $this->filesystem = $filesystem;
        $this->configCustomizers = $configCustomizers;
    }

    public function configure()
    {
        $this
            ->setName('shlink:install')
            ->setDescription('Installs or updates Shlink');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->processHelper = $this->getHelper('process');

        $this->io->writeln([
            '<info>Welcome to Shlink!!</info>',
            'This will guide you through the installation process.',
        ]);

        // Check if a cached config file exists and drop it if so
        if ($this->filesystem->exists('data/cache/app_config.php')) {
            $this->io->write('Deleting old cached config...');
            try {
                $this->filesystem->remove('data/cache/app_config.php');
                $this->io->writeln(' <info>Success</info>');
            } catch (IOException $e) {
                $this->io->writeln(
                    ' <error>Failed!</error> You will have to manually delete the data/cache/app_config.php file to get'
                    . ' new config applied.'
                );
                if ($this->io->isVerbose()) {
                    $this->getApplication()->renderException($e, $output);
                }
                return;
            }
        }

        // If running update command, ask the user to import previous config
        $config = $this->isUpdate ? $this->importConfig() : new CustomizableAppConfig();

        // Ask for custom config params
        foreach ([
            Plugin\DatabaseConfigCustomizer::class,
            Plugin\UrlShortenerConfigCustomizer::class,
            Plugin\LanguageConfigCustomizer::class,
            Plugin\ApplicationConfigCustomizer::class,
        ] as $pluginName) {
            /** @var Plugin\ConfigCustomizerInterface $configCustomizer */
            $configCustomizer = $this->configCustomizers->get($pluginName);
            $configCustomizer->process($this->io, $config);
        }

        // Generate config params files
        $this->configWriter->toFile(self::GENERATED_CONFIG_PATH, $config->getArrayCopy(), false);
        $this->io->writeln(['<info>Custom configuration properly generated!</info>', '']);

        // If current command is not update, generate database
        if (!  $this->isUpdate) {
            $this->io->writeln('Initializing database...');
            if (! $this->runCommand(
                'php vendor/bin/doctrine.php orm:schema-tool:create',
                'Error generating database.',
                $output
            )) {
                return;
            }
        }

        // Run database migrations
        $this->io->writeln('Updating database...');
        if (! $this->runCommand(
            'php vendor/bin/doctrine-migrations migrations:migrate',
            'Error updating database.',
            $output
        )) {
            return;
        }

        // Generate proxies
        $this->io->writeln('Generating proxies...');
        if (! $this->runCommand(
            'php vendor/bin/doctrine.php orm:generate-proxies',
            'Error generating proxies.',
            $output
        )) {
            return;
        }
    }

    /**
     * @return CustomizableAppConfig
     * @throws RuntimeException
     */
    private function importConfig(): CustomizableAppConfig
    {
        $config = new CustomizableAppConfig();

        // Ask the user if he/she wants to import an older configuration
        $importConfig = $this->io->confirm(
            '<question>Do you want to import previous configuration? (Y/n):</question> '
        );
        if (! $importConfig) {
            return $config;
        }

        // Ask the user for the older shlink path
        $keepAsking = true;
        do {
            $config->setImportedInstallationPath($this->ask(
                'Previous shlink installation path from which to import config'
            ));
            $configFile = $config->getImportedInstallationPath() . '/' . self::GENERATED_CONFIG_PATH;
            $configExists = $this->filesystem->exists($configFile);

            if (! $configExists) {
                $keepAsking = $this->io->confirm(
                    'Provided path does not seem to be a valid shlink root path. '
                    . '<question>Do you want to try another path? (Y/n):</question> '
                );
            }
        } while (! $configExists && $keepAsking);

        // If after some retries the user has chosen not to test another path, return
        if (! $configExists) {
            return $config;
        }

        // Read the config file
        $config->exchangeArray(include $configFile);
        return $config;
    }

    /**
     * @param string $text
     * @param string|null $default
     * @param bool $allowEmpty
     * @return string
     * @throws RuntimeException
     */
    private function ask($text, $default = null, $allowEmpty = false): string
    {
        if ($default !== null) {
            $text .= ' (defaults to ' . $default . ')';
        }

        do {
            $value = $this->io->ask('<question>' . $text . ':</question> ', $default);
            if (empty($value) && ! $allowEmpty) {
                $this->io->writeln('<error>Value can\'t be empty</error>');
            }
        } while (empty($value) && $default === null && ! $allowEmpty);

        return $value;
    }

    /**
     * @param string $command
     * @param string $errorMessage
     * @param OutputInterface $output
     * @return bool
     */
    private function runCommand($command, $errorMessage, OutputInterface $output): bool
    {
        $process = $this->processHelper->run($output, $command);
        if ($process->isSuccessful()) {
            $this->io->writeln('    <info>Success!</info>');
            return true;
        }

        if ($this->io->isVerbose()) {
            return false;
        }

        $this->io->writeln(
            '    <error>' . $errorMessage . '</error>  Run this command with -vvv to see specific error info.'
        );
        return false;
    }
}
