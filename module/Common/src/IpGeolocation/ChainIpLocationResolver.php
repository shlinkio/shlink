<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\IpGeolocation;

use Shlinkio\Shlink\Common\Exception\WrongIpException;

class ChainIpLocationResolver implements IpLocationResolverInterface
{
    /**
     * @var IpLocationResolverInterface[]
     */
    private $resolvers;

    public function __construct(IpLocationResolverInterface ...$resolvers)
    {
        $this->resolvers = $resolvers;
    }

    /**
     * @param string $ipAddress
     * @return array
     * @throws WrongIpException
     */
    public function resolveIpLocation(string $ipAddress): array
    {
        $error = null;

        foreach ($this->resolvers as $resolver) {
            try {
                return $resolver->resolveIpLocation($ipAddress);
            } catch (WrongIpException $e) {
                $error = $e;
            }
        }

        // If this instruction is reached, it means no resolver was capable of resolving the address
        throw WrongIpException::fromIpAddress($ipAddress, $error);
    }
}
