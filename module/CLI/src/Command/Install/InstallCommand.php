<?php
namespace Shlinkio\Shlink\CLI\Command\Install;

use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Config\Writer\WriterInterface;

class InstallCommand extends Command
{
    use StringUtilsTrait;

    const DATABASE_DRIVERS = [
        'MySQL' => 'pdo_mysql',
        'PostgreSQL' => 'pdo_pgsql',
        'SQLite' => 'pdo_sqlite',
    ];
    const SUPPORTED_LANGUAGES = ['en', 'es'];
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
     * @var string
     */
    private $importedInstallationPath;
    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var bool
     */
    private $isUpdate;
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * InstallCommand constructor.
     * @param WriterInterface $configWriter
     * @param Filesystem $filesystem
     * @param bool $isUpdate
     * @throws LogicException
     */
    public function __construct(WriterInterface $configWriter, Filesystem $filesystem, $isUpdate = false)
    {
        parent::__construct();
        $this->configWriter = $configWriter;
        $this->isUpdate = $isUpdate;
        $this->filesystem = $filesystem;
    }

    public function configure()
    {
        $this->setName('shlink:install')
            ->setDescription('Installs Shlink');
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
        $this->askDatabase($config);
        $this->askUrlShortener($config);
        $this->askLanguage($config);
        $this->askApplication($config);

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
            $this->importedInstallationPath = $this->ask(
                'Previous shlink installation path from which to import config'
            );
            $configFile = $this->importedInstallationPath . '/' . self::GENERATED_CONFIG_PATH;
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

    protected function askDatabase(CustomizableAppConfig $config)
    {
        $this->printTitle('DATABASE');

        if ($config->hasDatabase()) {
            $keepConfig = $this->questionHelper->ask($this->input, $this->output, new ConfirmationQuestion(
                '<question>Do you want to keep imported database config? (Y/n):</question> '
            ));
            if ($keepConfig) {
                // If the user selected to keep DB config and is configured to use sqlite, copy DB file
                if ($config->getDatabase()['DRIVER'] === self::DATABASE_DRIVERS['SQLite']) {
                    $this->filesystem->copy(
                        $this->importedInstallationPath . '/' . CustomizableAppConfig::SQLITE_DB_PATH,
                        CustomizableAppConfig::SQLITE_DB_PATH
                    );
                }

                return;
            }
        }

        // Select database type
        $params = [];
        $databases = array_keys(self::DATABASE_DRIVERS);
        $dbType = $this->questionHelper->ask($this->input, $this->output, new ChoiceQuestion(
            '<question>Select database type (defaults to ' . $databases[0] . '):</question>',
            $databases,
            0
        ));
        $params['DRIVER'] = self::DATABASE_DRIVERS[$dbType];

        // Ask for connection params if database is not SQLite
        if ($params['DRIVER'] !== self::DATABASE_DRIVERS['SQLite']) {
            $params['NAME'] = $this->ask('Database name', 'shlink');
            $params['USER'] = $this->ask('Database username');
            $params['PASSWORD'] = $this->ask('Database password');
            $params['HOST'] = $this->ask('Database host', 'localhost');
            $params['PORT'] = $this->ask('Database port', $this->getDefaultDbPort($params['DRIVER']));
        }

        $config->setDatabase($params);
    }

    protected function getDefaultDbPort($driver)
    {
        return $driver === 'pdo_mysql' ? '3306' : '5432';
    }

    protected function askUrlShortener(CustomizableAppConfig $config)
    {
        $this->printTitle('URL SHORTENER');

        if ($config->hasUrlShortener()) {
            $keepConfig = $this->questionHelper->ask($this->input, $this->output, new ConfirmationQuestion(
                '<question>Do you want to keep imported URL shortener config? (Y/n):</question> '
            ));
            if ($keepConfig) {
                return;
            }
        }

        // Ask for URL shortener params
        $config->setUrlShortener([
            'SCHEMA' => $this->questionHelper->ask($this->input, $this->output, new ChoiceQuestion(
                '<question>Select schema for generated short URLs (defaults to http):</question>',
                ['http', 'https'],
                0
            )),
            'HOSTNAME' => $this->ask('Hostname for generated URLs'),
            'CHARS' => $this->ask(
                'Character set for generated short codes (leave empty to autogenerate one)',
                null,
                true
            ) ?: str_shuffle(UrlShortener::DEFAULT_CHARS)
        ]);
    }

    protected function askLanguage(CustomizableAppConfig $config)
    {
        $this->printTitle('LANGUAGE');

        if ($config->hasLanguage()) {
            $keepConfig = $this->questionHelper->ask($this->input, $this->output, new ConfirmationQuestion(
                '<question>Do you want to keep imported language? (Y/n):</question> '
            ));
            if ($keepConfig) {
                return;
            }
        }

        $config->setLanguage([
            'DEFAULT' => $this->questionHelper->ask($this->input, $this->output, new ChoiceQuestion(
                '<question>Select default language for the application in general (defaults to '
                . self::SUPPORTED_LANGUAGES[0] . '):</question>',
                self::SUPPORTED_LANGUAGES,
                0
            )),
            'CLI' => $this->questionHelper->ask($this->input, $this->output, new ChoiceQuestion(
                '<question>Select default language for CLI executions (defaults to '
                . self::SUPPORTED_LANGUAGES[0] . '):</question>',
                self::SUPPORTED_LANGUAGES,
                0
            )),
        ]);
    }

    protected function askApplication(CustomizableAppConfig $config)
    {
        $this->printTitle('APPLICATION');

        if ($config->hasApp()) {
            $keepConfig = $this->questionHelper->ask($this->input, $this->output, new ConfirmationQuestion(
                '<question>Do you want to keep imported application config? (Y/n):</question> '
            ));
            if ($keepConfig) {
                return;
            }
        }

        $config->setApp([
            'SECRET' => $this->ask(
                'Define a secret string that will be used to sign API tokens (leave empty to autogenerate one)',
                null,
                true
            ) ?: $this->generateRandomString(32),
        ]);
    }

    /**
     * @param string $text
     */
    protected function printTitle($text)
    {
        $text = trim($text);
        $length = strlen($text) + 4;
        $header = str_repeat('*', $length);

        $this->output->writeln([
            '',
            '<info>' . $header . '</info>',
            '<info>* ' . strtoupper($text) . ' *</info>',
            '<info>' . $header . '</info>',
        ]);
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
