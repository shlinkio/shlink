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
    private $database;
    /**
     * @var array
     */
    private $urlShortener;
    /**
     * @var array
     */
    private $language;
    /**
     * @var array
     */
    private $app;
    /**
     * @var string
     */
    private $importedInstallationPath;

    /**
     * @return array
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param array $database
     * @return $this
     */
    public function setDatabase(array $database)
    {
        $this->database = $database;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasDatabase()
    {
        return ! empty($this->database);
    }

    /**
     * @return array
     */
    public function getUrlShortener()
    {
        return $this->urlShortener;
    }

    /**
     * @param array $urlShortener
     * @return $this
     */
    public function setUrlShortener(array $urlShortener)
    {
        $this->urlShortener = $urlShortener;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasUrlShortener()
    {
        return ! empty($this->urlShortener);
    }

    /**
     * @return array
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param array $language
     * @return $this
     */
    public function setLanguage(array $language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasLanguage()
    {
        return ! empty($this->language);
    }

    /**
     * @return array
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @param array $app
     * @return $this
     */
    public function setApp(array $app)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasApp()
    {
        return ! empty($this->app);
    }

    /**
     * @return string
     */
    public function getImportedInstallationPath()
    {
        return $this->importedInstallationPath;
    }

    /**
     * @param string $importedInstallationPath
     * @return $this|self
     */
    public function setImportedInstallationPath($importedInstallationPath)
    {
        $this->importedInstallationPath = $importedInstallationPath;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasImportedInstallationPath()
    {
        return $this->importedInstallationPath !== null;
    }

    /**
     * Exchange internal values from provided array
     *
     * @param array $array
     * @return void
     */
    public function exchangeArray(array $array)
    {
        if (isset($array['app_options'], $array['app_options']['secret_key'])) {
            $this->setApp([
                'SECRET' => $array['app_options']['secret_key'],
            ]);
        }

        if (isset($array['entity_manager'], $array['entity_manager']['connection'])) {
            $this->deserializeDatabase($array['entity_manager']['connection']);
        }

        if (isset($array['translator'], $array['translator']['locale'], $array['cli'], $array['cli']['locale'])) {
            $this->setLanguage([
                'DEFAULT' => $array['translator']['locale'],
                'CLI' => $array['cli']['locale'],
            ]);
        }

        if (isset($array['url_shortener'])) {
            $urlShortener = $array['url_shortener'];
            $this->setUrlShortener([
                'SCHEMA' => $urlShortener['domain']['schema'],
                'HOSTNAME' => $urlShortener['domain']['hostname'],
                'CHARS' => $urlShortener['shortcode_chars'],
                'VALIDATE_URL' => $urlShortener['validate_url'] ?? true,
            ]);
        }
    }

    private function deserializeDatabase(array $conn)
    {
        if (! isset($conn['driver'])) {
            return;
        }
        $driver = $conn['driver'];

        $params = ['DRIVER' => $driver];
        if ($driver !== 'pdo_sqlite') {
            $params['USER'] = $conn['user'];
            $params['PASSWORD'] = $conn['password'];
            $params['NAME'] = $conn['dbname'];
            $params['HOST'] = $conn['host'];
            $params['PORT'] = $conn['port'];
        }

        $this->setDatabase($params);
    }

    /**
     * Return an array representation of the object
     *
     * @return array
     */
    public function getArrayCopy()
    {
        $config = [
            'app_options' => [
                'secret_key' => $this->app['SECRET'],
                'disable_track_param' => $this->app['DISABLE_TRACK_PARAM'] ?? null,
            ],
            'entity_manager' => [
                'connection' => [
                    'driver' => $this->database['DRIVER'],
                ],
            ],
            'translator' => [
                'locale' => $this->language['DEFAULT'],
            ],
            'cli' => [
                'locale' => $this->language['CLI'],
            ],
            'url_shortener' => [
                'domain' => [
                    'schema' => $this->urlShortener['SCHEMA'],
                    'hostname' => $this->urlShortener['HOSTNAME'],
                ],
                'shortcode_chars' => $this->urlShortener['CHARS'],
                'validate_url' => $this->urlShortener['VALIDATE_URL'],
            ],
        ];

        // Build dynamic database config based on selected driver
        if ($this->database['DRIVER'] === 'pdo_sqlite') {
            $config['entity_manager']['connection']['path'] = self::SQLITE_DB_PATH;
        } else {
            $config['entity_manager']['connection']['user'] = $this->database['USER'];
            $config['entity_manager']['connection']['password'] = $this->database['PASSWORD'];
            $config['entity_manager']['connection']['dbname'] = $this->database['NAME'];
            $config['entity_manager']['connection']['host'] = $this->database['HOST'];
            $config['entity_manager']['connection']['port'] = $this->database['PORT'];

            if ($this->database['DRIVER'] === 'pdo_mysql') {
                $config['entity_manager']['connection']['driverOptions'] = [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                ];
            }
        }

        return $config;
    }
}
