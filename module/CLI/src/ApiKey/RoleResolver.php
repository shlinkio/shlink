<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\ApiKey;

use Shlinkio\Shlink\CLI\Command\Api\Input\ApiKeyInput;
use Shlinkio\Shlink\CLI\Exception\InvalidRoleConfigException;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;

/** @deprecated API key roles are deprecated */
readonly class RoleResolver implements RoleResolverInterface
{
    public function __construct(
        private DomainServiceInterface $domainService,
        private UrlShortenerOptions $urlShortenerOptions,
    ) {
    }

    public function determineRoles(ApiKeyInput $input): iterable
    {
        $domainAuthority = $input->domain;
        $author = $input->authorOnly;
        $noOrphanVisits = $input->noOrphanVisits;

        if ($author) {
            yield RoleDefinition::forAuthoredShortUrls();
        }
        if ($domainAuthority !== null) {
            yield $this->resolveRoleForAuthority($domainAuthority);
        }
        if ($noOrphanVisits) {
            yield RoleDefinition::forNoOrphanVisits();
        }
    }

    private function resolveRoleForAuthority(string $domainAuthority): RoleDefinition
    {
        if ($domainAuthority === $this->urlShortenerOptions->defaultDomain) {
            throw InvalidRoleConfigException::forDomainOnlyWithDefaultDomain();
        }

        $domain = $this->domainService->getOrCreate($domainAuthority);
        return RoleDefinition::forDomain($domain);
    }
}
