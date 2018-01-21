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
}
