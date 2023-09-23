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
    public function __construct(
        private readonly DomainServiceInterface $domainService,
        private readonly string $defaultDomain,
    ) {
    }

    public function determineRoles(InputInterface $input): iterable
    {
        $domainAuthority = $input->getOption(Role::DOMAIN_SPECIFIC->paramName());
        $author = $input->getOption(Role::AUTHORED_SHORT_URLS->paramName());

        if ($author) {
            yield RoleDefinition::forAuthoredShortUrls();
        }
        if (is_string($domainAuthority)) {
            yield $this->resolveRoleForAuthority($domainAuthority);
        }
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
