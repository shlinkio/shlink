<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\IpGeolocation\GeoLite2;

use Zend\Stdlib\AbstractOptions;

class GeoLite2Options extends AbstractOptions
{
    private $dbLocation = '';
    private $tempDir = '';
    private $downloadFrom = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz';

    public function getDbLocation(): string
    {
        return $this->dbLocation;
    }

    protected function setDbLocation(string $dbLocation): self
    {
        $this->dbLocation = $dbLocation;
        return $this;
    }

    public function getTempDir(): string
    {
        return $this->tempDir;
    }

    protected function setTempDir(string $tempDir): self
    {
        $this->tempDir = $tempDir;
        return $this;
    }

    public function getDownloadFrom(): string
    {
        return $this->downloadFrom;
    }

    protected function setDownloadFrom(string $downloadFrom): self
    {
        $this->downloadFrom = $downloadFrom;
        return $this;
    }
}
