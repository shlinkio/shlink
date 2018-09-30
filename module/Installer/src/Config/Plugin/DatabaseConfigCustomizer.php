<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Config\Plugin;

use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Shlinkio\Shlink\Installer\Util\AskUtilsTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DatabaseConfigCustomizer implements ConfigCustomizerInterface
{
    use AskUtilsTrait;

    private const DATABASE_DRIVERS = [
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
     * @throws IOException
     */
    public function process(SymfonyStyle $io, CustomizableAppConfig $appConfig): void
    {
        $io->title('DATABASE');

        if ($appConfig->hasDatabase() && $io->confirm('Do you want to keep imported database config?')) {
            // If the user selected to keep DB config and is configured to use sqlite, copy DB file
            if ($appConfig->getDatabase()['DRIVER'] === self::DATABASE_DRIVERS['SQLite']) {
                try {
                    $this->filesystem->copy(
                        $appConfig->getImportedInstallationPath() . '/' . CustomizableAppConfig::SQLITE_DB_PATH,
                        CustomizableAppConfig::SQLITE_DB_PATH
                    );
                } catch (IOException $e) {
                    $io->error('It wasn\'t possible to import the SQLite database');
                    throw $e;
                }
            }

            return;
        }

        // Select database type
        $params = [];
        $databases = \array_keys(self::DATABASE_DRIVERS);
        $dbType = $io->choice('Select database type', $databases, $databases[0]);
        $params['DRIVER'] = self::DATABASE_DRIVERS[$dbType];

        // Ask for connection params if database is not SQLite
        if ($params['DRIVER'] !== self::DATABASE_DRIVERS['SQLite']) {
            $params['NAME'] = $io->ask('Database name', 'shlink');
            $params['USER'] = $this->askRequired($io, 'username', 'Database username');
            $params['PASSWORD'] = $this->askRequired($io, 'password', 'Database password');
            $params['HOST'] = $io->ask('Database host', 'localhost');
            $params['PORT'] = $io->ask('Database port', $this->getDefaultDbPort($params['DRIVER']));
        }

        $appConfig->setDatabase($params);
    }

    private function getDefaultDbPort(string $driver): string
    {
        return $driver === 'pdo_mysql' ? '3306' : '5432';
    }
}
