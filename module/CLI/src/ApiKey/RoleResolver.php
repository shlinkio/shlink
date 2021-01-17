<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\ApiKey;

use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Symfony\Component\Console\Input\InputInterface;

class RoleResolver implements RoleResolverInterface
{
    private DomainServiceInterface $domainService;

    public function __construct(DomainServiceInterface $domainService)
    {
        $this->domainService = $domainService;
    }

    public function determineRoles(InputInterface $input): array
    {
        $domainAuthority = $input->getOption('domain-only');
        $author = $input->getOption('author-only');

        $roleDefinitions = [];
        if ($author) {
            $roleDefinitions[] = RoleDefinition::forAuthoredShortUrls();
        }
        if ($domainAuthority !== null) {
            $domain = $this->domainService->getOrCreate($domainAuthority);
            $roleDefinitions[] = RoleDefinition::forDomain($domain);
        }

        return $roleDefinitions;
    }
}
