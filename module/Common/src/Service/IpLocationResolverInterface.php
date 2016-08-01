<?php
namespace Shlinkio\Shlink\Common\Service;

interface IpLocationResolverInterface
{
    /**
     * @param $ipAddress
     * @return array
     */
    public function resolveIpLocation($ipAddress);
}
