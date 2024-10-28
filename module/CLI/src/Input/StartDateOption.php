<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Input;

use Cake\Chronos\Chronos;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

readonly final class StartDateOption
{
    private DateOption $dateOption;

    public function __construct(Command $command, string $descriptionHint)
    {
        $this->dateOption = new DateOption($command, 'start-date', 's', sprintf(
            'Allows to filter %s, returning only those older than provided date.',
            $descriptionHint,
        ));
    }

    public function get(InputInterface $input, OutputInterface $output): Chronos|null
    {
        return $this->dateOption->get($input, $output);
    }
}
