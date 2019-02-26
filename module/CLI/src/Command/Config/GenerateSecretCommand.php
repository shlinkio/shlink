<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Config;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

/** @deprecated */
class GenerateSecretCommand extends Command
{
    use StringUtilsTrait;

    public const NAME = 'config:generate-secret';

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('[DEPRECATED] Generates a random secret string that can be used for JWT token encryption')
            ->setHelp(
                '<fg=red;options=bold>This command is deprecated. Better leave shlink generate the secret key.</>'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $secret = $this->generateRandomString(32);
        (new SymfonyStyle($input, $output))->success(sprintf('Secret key: "%s"', $secret));
        return ExitCodes::EXIT_SUCCESS;
    }
}
