<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Domain;

use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_map;

#[AsCommand(
    name: ListDomainsCommand::NAME,
    description: 'List all domains that have been ever used for some short URL',
)]
class ListDomainsCommand extends Command
{
    public const string NAME = 'domain:list';

    public function __construct(private readonly DomainServiceInterface $domainService)
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(
            'Will display an extra column with the information of the "not found" redirects for every domain.',
            shortcut: 'r',
        )]
        bool $showRedirects = false,
    ): int {
        $domains = $this->domainService->listDomains();
        $commonFields = ['Domain', 'Is default'];
        $table = $showRedirects ? ShlinkTable::withRowSeparators($io) : ShlinkTable::default($io);

        $table->render(
            $showRedirects ? [...$commonFields, '"Not found" redirects'] : $commonFields,
            array_map(function (DomainItem $domain) use ($showRedirects) {
                $commonValues = [$domain->toString(), $domain->isDefault ? 'Yes' : 'No'];

                return $showRedirects
                    ? [
                        ...$commonValues,
                        $this->notFoundRedirectsToString($domain->notFoundRedirectConfig),
                    ]
                    : $commonValues;
            }, $domains),
        );

        return self::SUCCESS;
    }

    private function notFoundRedirectsToString(NotFoundRedirectConfigInterface $config): string
    {
        $baseUrl = $config->baseUrlRedirect ?? 'N/A';
        $regular404 = $config->regular404Redirect ?? 'N/A';
        $invalidShortUrl = $config->invalidShortUrlRedirect ?? 'N/A';

        return <<<EOL
        * Base URL: {$baseUrl}
        * Regular 404: {$regular404}
        * Invalid short URL: {$invalidShortUrl}
        EOL;
    }
}
