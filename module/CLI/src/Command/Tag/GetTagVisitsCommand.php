<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\CLI\Command\Visit\VisitsCommandUtils;
use Shlinkio\Shlink\CLI\Input\VisitsListInput;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Visit\Model\WithDomainVisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Ask;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(GetTagVisitsCommand::NAME, 'Returns the list of visits for provided tag')]
class GetTagVisitsCommand extends Command
{
    public const string NAME = 'tag:visits';

    public function __construct(private readonly VisitsStatsHelperInterface $visitsHelper)
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The tag which visits we want to get'), Ask('For what tag do you want to get visits')] string $tag,
        #[MapInput] VisitsListInput $input,
        #[Option(
            'Return visits that belong to this domain only. Use ' . Domain::DEFAULT_AUTHORITY . ' keyword for visits '
            . 'in default domain',
            shortcut: 'd',
        )]
        string|null $domain = null,
    ): int {
        $paginator = $this->visitsHelper->visitsForTag($tag, new WithDomainVisitsParams(
            dateRange: $input->dateRange(),
            domain: $domain,
        ));

        VisitsCommandUtils::renderOutput($io, $input, $paginator);

        return self::SUCCESS;
    }
}
