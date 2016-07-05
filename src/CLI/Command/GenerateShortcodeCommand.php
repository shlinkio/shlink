<?php
namespace Acelaya\UrlShortener\CLI\Command;

use Acelaya\UrlShortener\Exception\InvalidUrlException;
use Acelaya\UrlShortener\Service\UrlShortener;
use Acelaya\UrlShortener\Service\UrlShortenerInterface;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Zend\Diactoros\Uri;

class GenerateShortcodeCommand extends Command
{
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;
    /**
     * @var array
     */
    private $domainConfig;

    /**
     * GenerateShortcodeCommand constructor.
     * @param UrlShortenerInterface|UrlShortener $urlShortener
     * @param array $domainConfig
     *
     * @Inject({UrlShortener::class, "config.url_shortener.domain"})
     */
    public function __construct(UrlShortenerInterface $urlShortener, array $domainConfig)
    {
        parent::__construct(null);
        $this->urlShortener = $urlShortener;
        $this->domainConfig = $domainConfig;
    }

    public function configure()
    {
        $this->setName('shortcode:generate')
             ->setDescription('Generates a shortcode for provided URL and returns the short URL')
             ->addArgument('longUrl', InputArgument::REQUIRED, 'The long URL to parse');
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $longUrl = $input->getArgument('longUrl');
        if (! empty($longUrl)) {
            return;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question(
            '<question>A long URL was not provided. Which URL do you want to shorten?:</question> '
        );

        $longUrl = $helper->ask($input, $output, $question);
        if (! empty($longUrl)) {
            $input->setArgument('longUrl', $longUrl);
        }
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $longUrl = $input->getArgument('longUrl');

        try {
            if (! isset($longUrl)) {
                $output->writeln('<error>A URL was not provided!</error>');
                return;
            }

            $shortcode = $this->urlShortener->urlToShortCode(new Uri($longUrl));
            $shortUrl = (new Uri())->withPath($shortcode)
                ->withScheme($this->domainConfig['schema'])
                ->withHost($this->domainConfig['hostname']);

            $output->writeln([
                sprintf('Processed URL <info>%s</info>', $longUrl),
                sprintf('Generated URL <info>%s</info>', $shortUrl),
            ]);
        } catch (InvalidUrlException $e) {
            $output->writeln(
                sprintf('<error>Provided URL "%s" is invalid. Try with a different one.</error>', $longUrl)
            );
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e . '</error>');
        }
    }
}
