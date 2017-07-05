<?php
namespace Shlinkio\Shlink\CLI\Command\Install;

use Shlinkio\Shlink\CLI\Install\ConfigCustomizerPluginManagerInterface;
use Shlinkio\Shlink\CLI\Install\Plugin;
use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Config\Writer\WriterInterface;

class InstallCommand extends Command
{
    const GENERATED_CONFIG_PATH = 'config/params/generated_config.php';

    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var QuestionHelper
     */
    private $questionHelper;
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
     * @var ConfigCustomizerPluginManagerInterface
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
     * @param bool $isUpdate
     * @throws LogicException
     */
    public function __construct(
        WriterInterface $configWriter,
        Filesystem $filesystem,
        ConfigCustomizerPluginManagerInterface $configCustomizers,
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

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->questionHelper = $this->getHelper('question');
        $this->processHelper = $this->getHelper('process');

        $output->writeln([
            '<info>Welcome to Shlink!!</info>',
            'This will guide you through the installation process.',
        ]);

        // Check if a cached config file exists and drop it if so
        if ($this->filesystem->exists('data/cache/app_config.php')) {
            $output->write('Deleting old cached config...');
            try {
                $this->filesystem->remove('data/cache/app_config.php');
                $output->writeln(' <info>Success</info>');
            } catch (IOException $e) {
                $output->writeln(
                    ' <error>Failed!</error> You will have to manually delete the data/cache/app_config.php file to get'
                    . ' new config applied.'
                );
                if ($output->isVerbose()) {
                    $this->getApplication()->renderException($e, $output);
                }
                return;
            }
        }

        // If running update command, ask the user to import previous config
        $config = $this->isUpdate ? $this->importConfig() : new CustomizableAppConfig();

        // Ask for custom config params
        foreach ([
            Plugin\DatabaseConfigCustomizerPlugin::class,
            Plugin\UrlShortenerConfigCustomizerPlugin::class,
            Plugin\LanguageConfigCustomizerPlugin::class,
            Plugin\ApplicationConfigCustomizerPlugin::class,
        ] as $pluginName) {
            /** @var Plugin\ConfigCustomizerPluginInterface $configCustomizer */
            $configCustomizer = $this->configCustomizers->get($pluginName);
            $configCustomizer->process($input, $output, $config);
        }

        // Generate config params files
        $this->configWriter->toFile(self::GENERATED_CONFIG_PATH, $config->getArrayCopy(), false);
        $output->writeln(['<info>Custom configuration properly generated!</info>', '']);

        // If current command is not update, generate database
        if (!  $this->isUpdate) {
            $this->output->writeln('Initializing database...');
            if (! $this->runCommand(
                'php vendor/bin/doctrine.php orm:schema-tool:create',
                'Error generating database.'
            )) {
                return;
            }
        }

        // Run database migrations
        $output->writeln('Updating database...');
        if (! $this->runCommand('php vendor/bin/doctrine-migrations migrations:migrate', 'Error updating database.')) {
            return;
        }

        // Generate proxies
        $output->writeln('Generating proxies...');
        if (! $this->runCommand('php vendor/bin/doctrine.php orm:generate-proxies', 'Error generating proxies.')) {
            return;
        }
    }

    /**
     * @return CustomizableAppConfig
     * @throws RuntimeException
     */
    protected function importConfig()
    {
        $config = new CustomizableAppConfig();

        // Ask the user if he/she wants to import an older configuration
        $importConfig = $this->questionHelper->ask($this->input, $this->output, new ConfirmationQuestion(
            '<question>Do you want to import previous configuration? (Y/n):</question> '
        ));
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
                $keepAsking = $this->questionHelper->ask($this->input, $this->output, new ConfirmationQuestion(
                    'Provided path does not seem to be a valid shlink root path. '
                    . '<question>Do you want to try another path? (Y/n):</question> '
                ));
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
    protected function ask($text, $default = null, $allowEmpty = false)
    {
        if ($default !== null) {
            $text .= ' (defaults to ' . $default . ')';
        }
        do {
            $value = $this->questionHelper->ask($this->input, $this->output, new Question(
                '<question>' . $text . ':</question> ',
                $default
            ));
            if (empty($value) && ! $allowEmpty) {
                $this->output->writeln('<error>Value can\'t be empty</error>');
            }
        } while (empty($value) && $default === null && ! $allowEmpty);

        return $value;
    }

    /**
     * @param string $command
     * @param string $errorMessage
     * @return bool
     */
    protected function runCommand($command, $errorMessage)
    {
        $process = $this->processHelper->run($this->output, $command);
        if ($process->isSuccessful()) {
            $this->output->writeln('    <info>Success!</info>');
            return true;
        }

        if ($this->output->isVerbose()) {
            return false;
        }

        $this->output->writeln(
            '    <error>' . $errorMessage . '</error>  Run this command with -vvv to see specific error info.'
        );
        return false;
    }
}
