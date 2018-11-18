<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Config;

use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function sprintf;

class GenerateSecretCommand extends Command
{
    use StringUtilsTrait;

    public const NAME = 'config:generate-secret';

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('[DEPRECATED] Generates a random secret string that can be used for JWT token encryption');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $secret = $this->generateRandomString(32);
        (new SymfonyStyle($input, $output))->success(sprintf('Secret key: "%s"', $secret));
    }
}
