<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\IpGeolocation\Resolver;

use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model;

class ChainIpLocationResolver implements IpLocationResolverInterface
{
    /** @var IpLocationResolverInterface[] */
    private $resolvers;

    public function __construct(IpLocationResolverInterface ...$resolvers)
    {
        $this->resolvers = $resolvers;
    }

    /**
     * @throws WrongIpException
     */
    public function resolveIpLocation(string $ipAddress): Model\Location
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
