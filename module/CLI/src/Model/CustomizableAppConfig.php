<?php
namespace Shlinkio\Shlink\CLI\Model;

use Zend\Stdlib\ArraySerializableInterface;

final class CustomizableAppConfig implements ArraySerializableInterface
{
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
     * Exchange internal values from provided array
     *
     * @param array $array
     * @return void
     */
    public function exchangeArray(array $array)
    {

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
            ],
        ];

        // Build dynamic database config based on selected driver
        if ($this->database['DRIVER'] === 'pdo_sqlite') {
            $config['entity_manager']['connection']['path'] = 'data/database.sqlite';
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
