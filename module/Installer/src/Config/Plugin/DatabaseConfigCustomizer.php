<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Config\Plugin;

use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Shlinkio\Shlink\Installer\Util\AskUtilsTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use function array_diff;
use function array_keys;
use function Shlinkio\Shlink\Common\contains;

class DatabaseConfigCustomizer implements ConfigCustomizerInterface
{
    use AskUtilsTrait;

    public const DRIVER = 'DRIVER';
    public const NAME = 'NAME';
    public const USER = 'USER';
    public const PASSWORD = 'PASSWORD';
    public const HOST = 'HOST';
    public const PORT = 'PORT';
    private const DRIVER_DEPENDANT_OPTIONS = [
        self::DRIVER,
        self::NAME,
        self::USER,
        self::PASSWORD,
        self::HOST,
        self::PORT,
    ];
    private const EXPECTED_KEYS = self::DRIVER_DEPENDANT_OPTIONS; // Same now, but could change in the future

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
        $titlePrinted = false;
        $db = $appConfig->getDatabase();
        $doImport = $appConfig->hasDatabase();
        $keysToAskFor = $doImport ? array_diff(self::EXPECTED_KEYS, array_keys($db)) : self::EXPECTED_KEYS;

        // If the user selected to keep DB, try to import SQLite database
        if ($doImport) {
            $this->importSqliteDbFile($io, $appConfig);
        }

        if (empty($keysToAskFor)) {
            return;
        }

        // If the driver is one of the params to ask for, ask for it first
        if (contains(self::DRIVER, $keysToAskFor)) {
            $io->title('DATABASE');
            $titlePrinted = true;
            $db[self::DRIVER] = $this->ask($io, self::DRIVER);
            $keysToAskFor = array_diff($keysToAskFor, [self::DRIVER]);
        }

        // If driver is SQLite, do not ask any driver-dependant option
        if ($db[self::DRIVER] === self::DATABASE_DRIVERS['SQLite']) {
            $keysToAskFor = array_diff($keysToAskFor, self::DRIVER_DEPENDANT_OPTIONS);
        }

        if (! $titlePrinted && ! empty($keysToAskFor)) {
            $io->title('DATABASE');
        }
        foreach ($keysToAskFor as $key) {
            $db[$key] = $this->ask($io, $key, $db);
        }
        $appConfig->setDatabase($db);
    }

    private function importSqliteDbFile(SymfonyStyle $io, CustomizableAppConfig $appConfig): void
    {
        if ($appConfig->getDatabase()[self::DRIVER] !== self::DATABASE_DRIVERS['SQLite']) {
            return;
        }

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

    private function ask(SymfonyStyle $io, string $key, array $params = [])
    {
        switch ($key) {
            case self::DRIVER:
                $databases = array_keys(self::DATABASE_DRIVERS);
                $dbType = $io->choice('Select database type', $databases, $databases[0]);
                return self::DATABASE_DRIVERS[$dbType];
            case self::NAME:
                return $io->ask('Database name', 'shlink');
            case self::USER:
                return $this->askRequired($io, 'username', 'Database username');
            case self::PASSWORD:
                return $this->askRequired($io, 'password', 'Database password');
            case self::HOST:
                return $io->ask('Database host', 'localhost');
            case self::PORT:
                return $io->ask('Database port', $this->getDefaultDbPort($params[self::DRIVER]));
        }

        return '';
    }

    private function getDefaultDbPort(string $driver): string
    {
        return $driver === 'pdo_mysql' ? '3306' : '5432';
    }
}
