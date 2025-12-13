<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api\Input;

use Shlinkio\Shlink\Rest\ApiKey\Role;
use Symfony\Component\Console\Attribute\Option;

final class ApiKeyInput
{
    #[Option('The unique name by which this API key will be known', shortcut: 'm')]
    public string|null $name = null;

    #[Option('The date in which the API key should expire. Use any valid PHP format', shortcut: 'e')]
    public string|null $expirationDate = null;

    /** @deprecated */
    #[Option('Adds the "' . Role::AUTHORED_SHORT_URLS->value . '" role to the new API key', shortcut: 'a')]
    public bool $authorOnly = false;

    /** @deprecated */
    #[Option(
        'Adds the "' . Role::DOMAIN_SPECIFIC->value . '" role to the new API key, with provided domain',
        name: 'domain-only',
        shortcut: 'd',
    )]
    public string|null $domain = null;

    /** @deprecated */
    #[Option('Adds the "' . Role::NO_ORPHAN_VISITS->value . '" role to the new API key', shortcut: 'o')]
    public bool $noOrphanVisits = false;
}
