<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\ApiKey;

use Shlinkio\Shlink\CLI\Exception\InvalidRoleConfigException;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Symfony\Component\Console\Input\InputInterface;

use function is_string;

class RoleResolver implements RoleResolverInterface
{
    public function __construct(private DomainServiceInterface $domainService, private string $defaultDomain)
    {
    }

    public function determineRoles(InputInterface $input): array
    {
        $domainAuthority = $input->getOption(Role::DOMAIN_SPECIFIC->paramName());
        $author = $input->getOption(Role::AUTHORED_SHORT_URLS->paramName());

        $roleDefinitions = [];
        if ($author) {
            $roleDefinitions[] = RoleDefinition::forAuthoredShortUrls();
        }
        if (is_string($domainAuthority)) {
            $roleDefinitions[] = $this->resolveRoleForAuthority($domainAuthority);
        }

        return $roleDefinitions;
    }

    private function resolveRoleForAuthority(string $domainAuthority): RoleDefinition
    {
        if ($domainAuthority === $this->defaultDomain) {
            throw InvalidRoleConfigException::forDomainOnlyWithDefaultDomain();
        }

        $domain = $this->domainService->getOrCreate($domainAuthority);
        return RoleDefinition::forDomain($domain);
    }
}
