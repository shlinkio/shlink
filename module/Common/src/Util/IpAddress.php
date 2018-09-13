<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Util;

use Shlinkio\Shlink\Common\Exception\WrongIpException;

final class IpAddress
{
    private const IPV4_PARTS_COUNT = 4;
    private const OBFUSCATED_OCTET = '0';
    public const LOCALHOST = '127.0.0.1';

    /**
     * @var string
     */
    private $firstOctet;
    /**
     * @var string
     */
    private $secondOctet;
    /**
     * @var string
     */
    private $thirdOctet;
    /**
     * @var string
     */
    private $fourthOctet;
    /**
     * @var bool
     */
    private $isLocalhost;

    private function __construct()
    {
    }

    /**
     * @param string $address
     * @return IpAddress
     * @throws WrongIpException
     */
    public static function fromString(string $address): self
    {
        $address = \trim($address);
        $parts = \explode('.', $address);
        if (\count($parts) !== self::IPV4_PARTS_COUNT) {
            throw WrongIpException::fromIpAddress($address);
        }

        $instance = new self();
        $instance->isLocalhost = $address === self::LOCALHOST;
        [$instance->firstOctet, $instance->secondOctet, $instance->thirdOctet, $instance->fourthOctet] = $parts;
        return $instance;
    }

    public function getObfuscatedCopy(): self
    {
        $copy = clone $this;
        $copy->fourthOctet = $this->isLocalhost ? $this->fourthOctet : self::OBFUSCATED_OCTET;
        return $copy;
    }

    public function __toString(): string
    {
        return \implode('.', [
            $this->firstOctet,
            $this->secondOctet,
            $this->thirdOctet,
            $this->fourthOctet,
        ]);
    }
}
