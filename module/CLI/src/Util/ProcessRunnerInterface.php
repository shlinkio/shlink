<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Util;

use Symfony\Component\Console\Output\OutputInterface;

interface ProcessRunnerInterface
{
    /**
     * @param string[] $cmd
     */
    public function run(OutputInterface $output, array $cmd): void;
}
