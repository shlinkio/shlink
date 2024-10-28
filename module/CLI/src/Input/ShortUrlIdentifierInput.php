<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Input;

use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

readonly final class ShortUrlIdentifierInput
{
    public function __construct(Command $command, string $shortCodeDesc, string $domainDesc)
    {
        $command
            ->addArgument('shortCode', InputArgument::REQUIRED, $shortCodeDesc)
            ->addOption('domain', 'd', InputOption::VALUE_REQUIRED, $domainDesc);
    }

    public function shortCode(InputInterface $input): string|null
    {
        return $input->getArgument('shortCode');
    }

    public function toShortUrlIdentifier(InputInterface $input): ShortUrlIdentifier
    {
        $shortCode = $input->getArgument('shortCode');
        $domain = $input->getOption('domain');

        return ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain);
    }
}
