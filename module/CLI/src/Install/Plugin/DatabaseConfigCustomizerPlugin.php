<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Install\Plugin;

use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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

    public function __construct(Filesystem $filesystem)
    {
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
        $io = new SymfonyStyle($input, $output);
        $io->title('DATABASE');

        if ($appConfig->hasDatabase() && $io->confirm(
            '<question>Do you want to keep imported database config? (Y/n):</question> '
        )) {
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
        $dbType = $io->choice(
            '<question>Select database type (defaults to ' . $databases[0] . '):</question>',
            $databases,
            0
        );
        $params['DRIVER'] = self::DATABASE_DRIVERS[$dbType];

        // Ask for connection params if database is not SQLite
        if ($params['DRIVER'] !== self::DATABASE_DRIVERS['SQLite']) {
            $params['NAME'] = $this->ask($io, 'Database name', 'shlink');
            $params['USER'] = $this->ask($io, 'Database username');
            $params['PASSWORD'] = $this->ask($io, 'Database password');
            $params['HOST'] = $this->ask($io, 'Database host', 'localhost');
            $params['PORT'] = $this->ask($io, 'Database port', $this->getDefaultDbPort($params['DRIVER']));
        }

        $appConfig->setDatabase($params);
    }

    private function getDefaultDbPort($driver)
    {
        return $driver === 'pdo_mysql' ? '3306' : '5432';
    }
}
