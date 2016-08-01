<?php
namespace ShlinkioTest\Shlink\CLI\Command\Config;

use PHPUnit_Framework_TestCase as TestCase;
use Shlinkio\Shlink\CLI\Command\Config\GenerateCharsetCommand;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;

class GenerateCharsetCommandTest extends TestCase
{
    /**
     * @var CommandTester
     */
    protected $commandTester;

    public function setUp()
    {
        $command = new GenerateCharsetCommand(Translator::factory([]));
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function charactersAreGeneratedFromDefault()
    {
        $prefix = 'Character set: ';
        $prefixLength = strlen($prefix);

        $this->commandTester->execute([
            'command' => 'config:generate-charset',
        ]);
        $output = $this->commandTester->getDisplay();

        // Both default character set and the new one should have the same length
        $this->assertEquals($prefixLength + strlen(UrlShortener::DEFAULT_CHARS) + 1, strlen($output));

        // Both default character set and the new one should have the same characters
        $charset = substr($output, $prefixLength, strlen(UrlShortener::DEFAULT_CHARS));
        $orderedDefault = $this->orderStringLetters(UrlShortener::DEFAULT_CHARS);
        $orderedCharset = $this->orderStringLetters($charset);
        $this->assertEquals($orderedDefault, $orderedCharset);
    }

    protected function orderStringLetters($string)
    {
        $letters = str_split($string);
        sort($letters);
        return implode('', $letters);
    }
}
