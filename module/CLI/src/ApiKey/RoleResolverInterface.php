<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\ApiKey;

use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Symfony\Component\Console\Input\InputInterface;

interface RoleResolverInterface
{
    public const AUTHOR_ONLY_PARAM = 'author-only';
    public const DOMAIN_ONLY_PARAM = 'domain-only';

    /**
     * @return RoleDefinition[]
     */
    public function determineRoles(InputInterface $input): array;
}
