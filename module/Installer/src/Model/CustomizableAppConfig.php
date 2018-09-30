<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Model;

use Zend\Stdlib\ArraySerializableInterface;

final class CustomizableAppConfig implements ArraySerializableInterface
{
    public const SQLITE_DB_PATH = 'data/database.sqlite';

    /**
     * @var array
     */
    private $database = [];
    /**
     * @var array
     */
    private $urlShortener = [];
    /**
     * @var array
     */
    private $language = [];
    /**
     * @var array
     */
    private $app = [];
    /**
     * @var string|null
     */
    private $importedInstallationPath;

    public function getDatabase(): array
    {
        return $this->database;
    }

    public function setDatabase(array $database): self
    {
        $this->database = $database;
        return $this;
    }

    public function hasDatabase(): bool
    {
        return ! empty($this->database);
    }

    public function getUrlShortener(): array
    {
        return $this->urlShortener;
    }

    public function setUrlShortener(array $urlShortener): self
    {
        $this->urlShortener = $urlShortener;
        return $this;
    }

    public function hasUrlShortener(): bool
    {
        return ! empty($this->urlShortener);
    }

    public function getLanguage(): array
    {
        return $this->language;
    }

    public function setLanguage(array $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function hasLanguage(): bool
    {
        return ! empty($this->language);
    }

    public function getApp(): array
    {
        return $this->app;
    }

    public function setApp(array $app): self
    {
        $this->app = $app;
        return $this;
    }

    public function hasApp(): bool
    {
        return ! empty($this->app);
    }

    public function getImportedInstallationPath(): ?string
    {
        return $this->importedInstallationPath;
    }

    public function setImportedInstallationPath(string $importedInstallationPath): self
    {
        $this->importedInstallationPath = $importedInstallationPath;
        return $this;
    }

    public function hasImportedInstallationPath(): bool
    {
        return $this->importedInstallationPath !== null;
    }

    public function exchangeArray(array $array): void
    {
        $this->setApp([
            'SECRET' => $array['app_options']['secret_key'] ?? null,
            'DISABLE_TRACK_PARAM' => $array['app_options']['disable_track_param'] ?? null,
        ]);

        $this->deserializeDatabase($array['entity_manager']['connection'] ?? []);

        $this->setLanguage([
            'DEFAULT' => $array['translator']['locale'] ?? null,
            'CLI' => $array['cli']['locale'] ?? null,
        ]);

        $this->setUrlShortener([
            'SCHEMA' => $array['url_shortener']['domain']['schema'] ?? null,
            'HOSTNAME' => $array['url_shortener']['domain']['hostname'] ?? null,
            'CHARS' => $array['url_shortener']['shortcode_chars'] ?? null,
            'VALIDATE_URL' => $array['url_shortener']['validate_url'] ?? true,
        ]);
    }

    private function deserializeDatabase(array $conn): void
    {
        if (! isset($conn['driver'])) {
            return;
        }
        $driver = $conn['driver'];

        $params = ['DRIVER' => $driver];
        if ($driver !== 'pdo_sqlite') {
            $params['USER'] = $conn['user'] ?? null;
            $params['PASSWORD'] = $conn['password'] ?? null;
            $params['NAME'] = $conn['dbname'] ?? null;
            $params['HOST'] = $conn['host'] ?? null;
            $params['PORT'] = $conn['port'] ?? null;
        }

        $this->setDatabase($params);
    }

    public function getArrayCopy(): array
    {
        $dbDriver = $this->database['DRIVER'] ?? '';
        $config = [
            'app_options' => [
                'secret_key' => $this->app['SECRET'] ?? '',
                'disable_track_param' => $this->app['DISABLE_TRACK_PARAM'] ?? null,
            ],
            'entity_manager' => [
                'connection' => [
                    'driver' => $dbDriver,
                ],
            ],
            'translator' => [
                'locale' => $this->language['DEFAULT'] ?? 'en',
            ],
            'cli' => [
                'locale' => $this->language['CLI'] ?? 'en',
            ],
            'url_shortener' => [
                'domain' => [
                    'schema' => $this->urlShortener['SCHEMA'] ?? 'http',
                    'hostname' => $this->urlShortener['HOSTNAME'] ?? '',
                ],
                'shortcode_chars' => $this->urlShortener['CHARS'] ?? '',
                'validate_url' => $this->urlShortener['VALIDATE_URL'] ?? true,
            ],
        ];

        // Build dynamic database config based on selected driver
        if ($dbDriver === 'pdo_sqlite') {
            $config['entity_manager']['connection']['path'] = self::SQLITE_DB_PATH;
        } else {
            $config['entity_manager']['connection']['user'] = $this->database['USER'] ?? '';
            $config['entity_manager']['connection']['password'] = $this->database['PASSWORD'] ?? '';
            $config['entity_manager']['connection']['dbname'] = $this->database['NAME'] ?? '';
            $config['entity_manager']['connection']['host'] = $this->database['HOST'] ?? '';
            $config['entity_manager']['connection']['port'] = $this->database['PORT'] ?? '';

            if ($dbDriver === 'pdo_mysql') {
                $config['entity_manager']['connection']['driverOptions'] = [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                ];
            }
        }

        return $config;
    }
}
