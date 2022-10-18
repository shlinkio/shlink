<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\ApiKey;

use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Symfony\Component\Console\Input\InputInterface;

interface RoleResolverInterface
{
    /**
     * @return RoleDefinition[]
     */
    public function determineRoles(InputInterface $input): array;
}
