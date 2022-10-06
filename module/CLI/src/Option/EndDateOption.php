<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Option;

use Cake\Chronos\Chronos;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

class EndDateOption
{
    private readonly DateOption $dateOption;

    public function __construct(Command $command, string $descriptionHint)
    {
        $this->dateOption = new DateOption($command, 'end-date', 'e', sprintf(
            'Allows to filter %s, returning only those newer than provided date.',
            $descriptionHint,
        ));
    }

    public function get(InputInterface $input, OutputInterface $output): ?Chronos
    {
        return $this->dateOption->get($input, $output);
    }
}
