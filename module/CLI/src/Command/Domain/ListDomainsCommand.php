<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Domain;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Entity\Domain;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Functional\map;

class ListDomainsCommand extends Command
{
    public const NAME = 'domain:list';

    private DomainServiceInterface $domainService;
    private string $defaultDomain;

    public function __construct(DomainServiceInterface $domainService, string $defaultDomain)
    {
        parent::__construct();
        $this->domainService = $domainService;
        $this->defaultDomain = $defaultDomain;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List all domains that have been ever used for some short URL');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $regularDomains = $this->domainService->listDomainsWithout($this->defaultDomain);

        ShlinkTable::fromOutput($output)->render(['Domain', 'Is default'], [
            [$this->defaultDomain, 'Yes'],
            ...map($regularDomains, fn (Domain $domain) => [$domain->getAuthority(), 'No']),
        ]);

        return ExitCodes::EXIT_SUCCESS;
    }
}
