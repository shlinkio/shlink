<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Domain;

use Shlinkio\Shlink\CLI\Command\Visit\VisitsCommandUtils;
use Shlinkio\Shlink\CLI\Input\VisitsListInput;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Ask;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(GetDomainVisitsCommand::NAME, 'Returns the list of visits for provided domain')]
class GetDomainVisitsCommand extends Command
{
    public const string NAME = 'domain:visits';

    public function __construct(private readonly VisitsStatsHelperInterface $visitsHelper)
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The domain which visits we want to get'), Ask('For what domain do you want to get visits?')]
        string $domain,
        #[MapInput] VisitsListInput $input,
    ): int {
        $paginator = $this->visitsHelper->visitsForDomain($domain, new VisitsParams($input->dateRange()));
        VisitsCommandUtils::renderOutput($io, $input, $paginator);

        return self::SUCCESS;
    }
}
