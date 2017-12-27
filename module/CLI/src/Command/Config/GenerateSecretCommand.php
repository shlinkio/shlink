<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Config;

use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\I18n\Translator\TranslatorInterface;

class GenerateSecretCommand extends Command
{
    use StringUtilsTrait;

    const NAME = 'config:generate-secret';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        parent::__construct();
    }

    public function configure()
    {
        $this->setName(self::NAME)
             ->setDescription($this->translator->translate(
                 'Generates a random secret string that can be used for JWT token encryption'
             ));
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $secret = $this->generateRandomString(32);
        $output->writeln($this->translator->translate('Secret key:') . sprintf(' <info>%s</info>', $secret));
    }
}
