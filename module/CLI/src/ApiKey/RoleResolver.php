<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\ApiKey;

use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Symfony\Component\Console\Input\InputInterface;

use function is_string;

class RoleResolver implements RoleResolverInterface
{
    public function __construct(private DomainServiceInterface $domainService)
    {
    }

    public function determineRoles(InputInterface $input): array
    {
        $domainAuthority = $input->getOption('domain-only');
        $author = $input->getOption('author-only');

        $roleDefinitions = [];
        if ($author) {
            $roleDefinitions[] = RoleDefinition::forAuthoredShortUrls();
        }
        if (is_string($domainAuthority)) {
            $domain = $this->domainService->getOrCreate($domainAuthority);
            $roleDefinitions[] = RoleDefinition::forDomain($domain);
        }

        return $roleDefinitions;
    }
}
