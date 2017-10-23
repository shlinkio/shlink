<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Install\Plugin;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

abstract class AbstractConfigCustomizerPlugin implements ConfigCustomizerPluginInterface
{
    /**
     * @var QuestionHelper
     */
    protected $questionHelper;

    public function __construct(QuestionHelper $questionHelper)
    {
        $this->questionHelper = $questionHelper;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $text
     * @param string|null $default
     * @param bool $allowEmpty
     * @return string
     * @throws RuntimeException
     */
    protected function ask(InputInterface $input, OutputInterface $output, $text, $default = null, $allowEmpty = false)
    {
        if ($default !== null) {
            $text .= ' (defaults to ' . $default . ')';
        }
        do {
            $value = $this->questionHelper->ask($input, $output, new Question(
                '<question>' . $text . ':</question> ',
                $default
            ));
            if (empty($value) && ! $allowEmpty) {
                $output->writeln('<error>Value can\'t be empty</error>');
            }
        } while (empty($value) && $default === null && ! $allowEmpty);

        return $value;
    }

    /**
     * @param OutputInterface $output
     * @param string $text
     */
    protected function printTitle(OutputInterface $output, $text)
    {
        $text = trim($text);
        $length = strlen($text) + 4;
        $header = str_repeat('*', $length);

        $output->writeln([
            '',
            '<info>' . $header . '</info>',
            '<info>* ' . strtoupper($text) . ' *</info>',
            '<info>' . $header . '</info>',
        ]);
    }
}
