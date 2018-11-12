<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\Common\Exception\RuntimeException;
use Shlinkio\Shlink\Common\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\I18n\Translator\TranslatorInterface;

class UpdateDbCommand extends Command
{
    public const NAME = 'visit:update-db';

    /**
     * @var DbUpdaterInterface
     */
    private $geoLiteDbUpdater;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(DbUpdaterInterface $geoLiteDbUpdater, TranslatorInterface $translator)
    {
        $this->geoLiteDbUpdater = $geoLiteDbUpdater;
        $this->translator = $translator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription(
                $this->translator->translate('Updates the GeoLite2 database file used to geolocate IP addresses')
            )
            ->setHelp($this->translator->translate(
                'The GeoLite2 database is updated first Tuesday every month, so this command should be ideally run '
                . 'every first Wednesday'
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->geoLiteDbUpdater->downloadFreshCopy();
            $io->success($this->translator->translate('GeoLite2 database properly updated'));
        } catch (RuntimeException $e) {
            $io->error($this->translator->translate('An error occurred while updating GeoLite2 database'));
            if ($io->isVerbose()) {
                $this->getApplication()->renderException($e, $output);
            }
        }
    }
}
