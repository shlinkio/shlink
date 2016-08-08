<?php
namespace Shlinkio\Shlink\CLI\Command\Shortcode;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Zend\I18n\Translator\TranslatorInterface;

class ResolveUrlCommand extends Command
{
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ResolveUrlCommand constructor.
     * @param UrlShortenerInterface $urlShortener
     * @param TranslatorInterface $translator
     *
     * @Inject({UrlShortener::class, "translator"})
     */
    public function __construct(UrlShortenerInterface $urlShortener, TranslatorInterface $translator)
    {
        $this->urlShortener = $urlShortener;
        $this->translator = $translator;
        parent::__construct(null);
    }

    public function configure()
    {
        $this->setName('shortcode:parse')
             ->setDescription($this->translator->translate('Returns the long URL behind a short code'))
             ->addArgument(
                 'shortCode',
                 InputArgument::REQUIRED,
                 $this->translator->translate('The short code to parse')
             );
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $shortCode = $input->getArgument('shortCode');
        if (! empty($shortCode)) {
            return;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question(sprintf(
            '<question>%s</question> ',
            $this->translator->translate('A short code was not provided. Which short code do you want to parse?:')
        ));

        $shortCode = $helper->ask($input, $output, $question);
        if (! empty($shortCode)) {
            $input->setArgument('shortCode', $shortCode);
        }
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $shortCode = $input->getArgument('shortCode');

        try {
            $longUrl = $this->urlShortener->shortCodeToUrl($shortCode);
            if (! isset($longUrl)) {
                $output->writeln(sprintf(
                    '<error>' . $this->translator->translate('No URL found for short code "%s"') . '</error>',
                    $shortCode
                ));
                return;
            }

            $output->writeln(sprintf('%s <info>%s</info>', $this->translator->translate('Long URL:'), $longUrl));
        } catch (InvalidShortCodeException $e) {
            $output->writeln(sprintf('<error>' . $this->translator->translate(
                'Provided short code "%s" has an invalid format.'
            ) . '</error>', $shortCode));
        }
    }
}
