<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Service;

interface IpLocationResolverInterface
{
    /**
     * @param $ipAddress
     * @return array
     */
    public function resolveIpLocation($ipAddress);
}
