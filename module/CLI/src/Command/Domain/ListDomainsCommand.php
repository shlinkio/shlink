<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Domain;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Functional\map;

class ListDomainsCommand extends Command
{
    public const NAME = 'domain:list';

    private DomainServiceInterface $domainService;

    public function __construct(DomainServiceInterface $domainService)
    {
        parent::__construct();
        $this->domainService = $domainService;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List all domains that have been ever used for some short URL');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $domains = $this->domainService->listDomains();

        ShlinkTable::fromOutput($output)->render(
            ['Domain', 'Is default'],
            map($domains, fn (DomainItem $domain) => [$domain->toString(), $domain->isDefault() ? 'Yes' : 'No']),
        );

        return ExitCodes::EXIT_SUCCESS;
    }
}
