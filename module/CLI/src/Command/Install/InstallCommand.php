<?php
namespace Shlinkio\Shlink\CLI\Command\Install;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class InstallCommand extends Command
{
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var OutputInterface
     */
    private $output;

    public function configure()
    {
        $this->setName('shlink:install')
             ->setDescription('Installs Shlink');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $params = [];

        $output->writeln([
            '<info>Welcome to Shlink!!</info>',
            'This will guide you through the installation process.',
        ]);

        $params['DB_NAME'] = $this->ask('Database name', 'shlink');
    }

    /**
     * @param string $text
     * @param string|null $default
     * @return string
     */
    protected function ask($text, $default = null)
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        if (isset($default)) {
            $text .= ' (defaults to ' . $default . ')';
        }
        return $questionHelper->ask($this->input, $this->output, new Question(
            '    <question>' . $text . ':</question> ',
            $default
        ));
    }
}
