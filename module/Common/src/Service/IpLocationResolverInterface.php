<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Service;

use Shlinkio\Shlink\Common\Exception\WrongIpException;

interface IpLocationResolverInterface
{
    /**
     * @param string $ipAddress
     * @return array
     * @throws WrongIpException
     */
    public function resolveIpLocation(string $ipAddress): array;

    /**
     * Returns the interval in seconds that needs to be waited when the API limit is reached
     *
     * @return int
     */
    public function getApiInterval(): int;

    /**
     * Returns the limit of requests that can be performed to the API in a specific interval, or null if no limit exists
     *
     * @return int|null
     */
    public function getApiLimit(): ?int;
}
