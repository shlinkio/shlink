<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Config;

use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\I18n\Translator\TranslatorInterface;
use function sprintf;

class GenerateSecretCommand extends Command
{
    use StringUtilsTrait;

    public const NAME = 'config:generate-secret';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::NAME)
             ->setDescription($this->translator->translate(
                 'Generates a random secret string that can be used for JWT token encryption'
             ));
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $secret = $this->generateRandomString(32);
        (new SymfonyStyle($input, $output))->success(
            sprintf($this->translator->translate('Secret key: "%s"'), $secret)
        );
    }
}
