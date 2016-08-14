<?php
namespace Shlinkio\Shlink\CLI\Command\Install;

use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
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
     * @var WriterInterface
     */
    private $configWriter;

    public function __construct(WriterInterface $configWriter)
    {
        parent::__construct(null);
        $this->configWriter = $configWriter;
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
        $params = [];

        $output->writeln([
            '<info>Welcome to Shlink!!</info>',
            'This will guide you through the installation process.',
        ]);

        // Check if a cached config file exists and drop it if so
        if (file_exists('data/cache/app_config.php')) {
            $output->write('Deleting old cached config...');
            if (unlink('data/cache/app_config.php')) {
                $output->writeln(' <info>Success</info>');
            } else {
                $output->writeln(
                    ' <error>Failed!</error> You will have to manually delete the data/cache/app_config.php file to get'
                    . ' new config applied.'
                );
            }
        }

        // Ask for custom config params
        $params['DATABASE'] = $this->askDatabase();
        $params['URL_SHORTENER'] = $this->askUrlShortener();
        $params['LANGUAGE'] = $this->askLanguage();
        $params['APP'] = $this->askApplication();

        // Generate config params files
        $config = $this->buildAppConfig($params);
        $this->configWriter->toFile('config/params/generated_config.php', $config);
        $output->writeln('<info>Custom configuration properly generated!</info>');
    }

    protected function askDatabase()
    {
        $params = [];
        $this->printTitle('DATABASE');

        // Select database type
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
        }

        return $params;
    }

    protected function askUrlShortener()
    {
        $this->printTitle('URL SHORTENER');

        // Ask for URL shortener params
        return [
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
        ];
    }

    protected function askLanguage()
    {
        $this->printTitle('LANGUAGE');

        return [
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
        ];
    }

    protected function askApplication()
    {
        $this->printTitle('APPLICATION');

        return [
            'SECRET' => $this->ask(
                'Define a secret string that will be used to sign API tokens (leave empty to autogenerate one)',
                null,
                true
            ) ?: $this->generateRandomString(32),
        ];
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
     */
    protected function ask($text, $default = null, $allowEmpty = false)
    {
        if (isset($default)) {
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
        } while (empty($value) && empty($default) && ! $allowEmpty);

        return $value;
    }

    /**
     * @param array $params
     * @return array
     */
    protected function buildAppConfig(array $params)
    {
        // Build simple config
        $config = [
            'app_options' => [
                'secret_key' => $params['APP']['SECRET'],
            ],
            'entity_manager' => [
                'connection' => [
                    'driver' => $params['DATABASE']['DRIVER'],
                ],
            ],
            'translator' => [
                'locale' => $params['LANGUAGE']['DEFAULT'],
            ],
            'url_shortener' => [
                'domain' => [
                    'schema' => $params['URL_SHORTENER']['SCHEMA'],
                    'hostname' => $params['URL_SHORTENER']['HOSTNAME'],
                ],
                'shortcode_chars' => $params['URL_SHORTENER']['CHARS'],
            ],
        ];

        // Build dynamic database config
        if ($params['DATABASE']['DRIVER'] === 'pdo_sqlite') {
            $config['entity_manager']['connection']['path'] = 'data/database.sqlite';
        } else {
            $config['entity_manager']['connection']['user'] = $params['DATABASE']['USER'];
            $config['entity_manager']['connection']['password'] = $params['DATABASE']['PASSWORD'];
            $config['entity_manager']['connection']['dbname'] = $params['DATABASE']['NAME'];
        }

        return $config;
    }
}
