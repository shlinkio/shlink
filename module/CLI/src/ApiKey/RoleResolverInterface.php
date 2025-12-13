<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\ApiKey;

use Shlinkio\Shlink\CLI\Command\Api\Input\ApiKeyInput;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;

/** @deprecated API key roles are deprecated */
interface RoleResolverInterface
{
    /**
     * @return iterable<RoleDefinition>
     */
    public function determineRoles(ApiKeyInput $input): iterable;
}
