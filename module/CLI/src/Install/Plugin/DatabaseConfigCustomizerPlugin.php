<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Install\Plugin;

use Acelaya\ZsmAnnotatedServices\Annotation as DI;
use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DatabaseConfigCustomizerPlugin extends AbstractConfigCustomizerPlugin
{
    const DATABASE_DRIVERS = [
        'MySQL' => 'pdo_mysql',
        'PostgreSQL' => 'pdo_pgsql',
        'SQLite' => 'pdo_sqlite',
    ];

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * DatabaseConfigCustomizerPlugin constructor.
     * @param QuestionHelper $questionHelper
     * @param Filesystem $filesystem
     *
     * @DI\Inject({QuestionHelper::class, Filesystem::class})
     */
    public function __construct(QuestionHelper $questionHelper, Filesystem $filesystem)
    {
        parent::__construct($questionHelper);
        $this->filesystem = $filesystem;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param CustomizableAppConfig $appConfig
     * @return void
     * @throws IOException
     * @throws RuntimeException
     */
    public function process(InputInterface $input, OutputInterface $output, CustomizableAppConfig $appConfig)
    {
        $this->printTitle($output, 'DATABASE');

        if ($appConfig->hasDatabase() && $this->questionHelper->ask($input, $output, new ConfirmationQuestion(
            '<question>Do you want to keep imported database config? (Y/n):</question> '
        ))) {
            // If the user selected to keep DB config and is configured to use sqlite, copy DB file
            if ($appConfig->getDatabase()['DRIVER'] === self::DATABASE_DRIVERS['SQLite']) {
                try {
                    $this->filesystem->copy(
                        $appConfig->getImportedInstallationPath() . '/' . CustomizableAppConfig::SQLITE_DB_PATH,
                        CustomizableAppConfig::SQLITE_DB_PATH
                    );
                } catch (IOException $e) {
                    $output->writeln('<error>It wasn\'t possible to import the SQLite database</error>');
                    throw $e;
                }
            }

            return;
        }

        // Select database type
        $params = [];
        $databases = array_keys(self::DATABASE_DRIVERS);
        $dbType = $this->questionHelper->ask($input, $output, new ChoiceQuestion(
            '<question>Select database type (defaults to ' . $databases[0] . '):</question>',
            $databases,
            0
        ));
        $params['DRIVER'] = self::DATABASE_DRIVERS[$dbType];

        // Ask for connection params if database is not SQLite
        if ($params['DRIVER'] !== self::DATABASE_DRIVERS['SQLite']) {
            $params['NAME'] = $this->ask($input, $output, 'Database name', 'shlink');
            $params['USER'] = $this->ask($input, $output, 'Database username');
            $params['PASSWORD'] = $this->ask($input, $output, 'Database password');
            $params['HOST'] = $this->ask($input, $output, 'Database host', 'localhost');
            $params['PORT'] = $this->ask($input, $output, 'Database port', $this->getDefaultDbPort($params['DRIVER']));
        }

        $appConfig->setDatabase($params);
    }

    private function getDefaultDbPort($driver)
    {
        return $driver === 'pdo_mysql' ? '3306' : '5432';
    }
}
