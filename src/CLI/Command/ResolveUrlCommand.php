<?php
namespace Acelaya\UrlShortener\CLI\Command;

use Acelaya\UrlShortener\Exception\InvalidShortCodeException;
use Acelaya\UrlShortener\Service\UrlShortener;
use Acelaya\UrlShortener\Service\UrlShortenerInterface;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ResolveUrlCommand extends Command
{
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;

    /**
     * ResolveUrlCommand constructor.
     * @param UrlShortenerInterface|UrlShortener $urlShortener
     *
     * @Inject({UrlShortener::class})
     */
    public function __construct(UrlShortenerInterface $urlShortener)
    {
        parent::__construct(null);
        $this->urlShortener = $urlShortener;
    }

    public function configure()
    {
        $this->setName('shortcode:parse')
             ->setDescription('Returns the long URL behind a short code')
             ->addArgument('shortCode', InputArgument::REQUIRED, 'The short code to parse');
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $shortCode = $input->getArgument('shortCode');
        if (! empty($shortCode)) {
            return;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question(
            '<question>A short code was not provided. Which short code do you want to parse?:</question> '
        );

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
                $output->writeln(sprintf('<error>No URL found for short code "%s"</error>', $shortCode));
                return;
            }

            $output->writeln(sprintf('Long URL <info>%s</info>', $longUrl));
        } catch (InvalidShortCodeException $e) {
            $output->writeln(
                sprintf('<error>Provided short code "%s" has an invalid format.</error>', $shortCode)
            );
        }
    }
}
