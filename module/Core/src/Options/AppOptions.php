<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Zend\Stdlib\AbstractOptions;
use function sprintf;

class AppOptions extends AbstractOptions
{
    use StringUtilsTrait;

    /**
     * @var string
     */
    protected $name = '';
    /**
     * @var string
     */
    protected $version = '1.0';
    /**
     * @var string
     */
    protected $secretKey = '';
    /**
     * @var string|null
     */
    protected $disableTrackParam;

    /**
     * AppOptions constructor.
     * @param array|null|\Traversable $options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    protected function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return $this
     */
    protected function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * @param mixed $secretKey
     * @return $this
     */
    protected function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDisableTrackParam()
    {
        return $this->disableTrackParam;
    }

    /**
     * @param string|null $disableTrackParam
     * @return $this|self
     */
    protected function setDisableTrackParam($disableTrackParam): self
    {
        $this->disableTrackParam = $disableTrackParam;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s:v%s', $this->name, $this->version);
    }
}
