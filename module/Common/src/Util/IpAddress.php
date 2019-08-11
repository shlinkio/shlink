<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Util;

use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;

use function count;
use function explode;
use function implode;
use function sprintf;
use function trim;

final class IpAddress
{
    private const IPV4_PARTS_COUNT = 4;
    private const OBFUSCATED_OCTET = '0';
    public const LOCALHOST = '127.0.0.1';

    /** @var string */
    private $firstOctet;
    /** @var string */
    private $secondOctet;
    /** @var string */
    private $thirdOctet;
    /** @var string */
    private $fourthOctet;

    private function __construct(string $firstOctet, string $secondOctet, string $thirdOctet, string $fourthOctet)
    {
        $this->firstOctet = $firstOctet;
        $this->secondOctet = $secondOctet;
        $this->thirdOctet = $thirdOctet;
        $this->fourthOctet = $fourthOctet;
    }

    /**
     * @param string $address
     * @return IpAddress
     * @throws InvalidArgumentException
     */
    public static function fromString(string $address): self
    {
        $address = trim($address);
        $parts = explode('.', $address);
        if (count($parts) !== self::IPV4_PARTS_COUNT) {
            throw new InvalidArgumentException(sprintf('Provided IP "%s" is invalid', $address));
        }

        return new self(...$parts);
    }

    public function getObfuscatedCopy(): self
    {
        return new self(
            $this->firstOctet,
            $this->secondOctet,
            $this->thirdOctet,
            self::OBFUSCATED_OCTET
        );
    }

    public function __toString(): string
    {
        return implode('.', [
            $this->firstOctet,
            $this->secondOctet,
            $this->thirdOctet,
            $this->fourthOctet,
        ]);
    }
}
