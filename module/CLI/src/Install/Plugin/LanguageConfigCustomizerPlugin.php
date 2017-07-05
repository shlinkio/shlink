<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Install\Plugin;

use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class LanguageConfigCustomizerPlugin extends AbstractConfigCustomizerPlugin
{
    const SUPPORTED_LANGUAGES = ['en', 'es'];

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param CustomizableAppConfig $appConfig
     * @return void
     * @throws RuntimeException
     */
    public function process(InputInterface $input, OutputInterface $output, CustomizableAppConfig $appConfig)
    {
        $this->printTitle($output, 'LANGUAGE');

        if ($appConfig->hasLanguage()) {
            $keepConfig = $this->questionHelper->ask($input, $output, new ConfirmationQuestion(
                '<question>Do you want to keep imported language? (Y/n):</question> '
            ));
            if ($keepConfig) {
                return;
            }
        }

        $appConfig->setLanguage([
            'DEFAULT' => $this->questionHelper->ask($input, $output, new ChoiceQuestion(
                '<question>Select default language for the application in general (defaults to '
                . self::SUPPORTED_LANGUAGES[0] . '):</question>',
                self::SUPPORTED_LANGUAGES,
                0
            )),
            'CLI' => $this->questionHelper->ask($input, $output, new ChoiceQuestion(
                '<question>Select default language for CLI executions (defaults to '
                . self::SUPPORTED_LANGUAGES[0] . '):</question>',
                self::SUPPORTED_LANGUAGES,
                0
            )),
        ]);
    }
}
