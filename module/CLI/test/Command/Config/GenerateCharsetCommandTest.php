<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Config;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Config\GenerateCharsetCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;
use function implode;
use function sort;
use function str_split;

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

        $this->commandTester->execute([
            'command' => 'config:generate-charset',
        ]);
        $output = $this->commandTester->getDisplay();

        // Both default character set and the new one should have the same length
        $this->assertContains($prefix, $output);
    }

    protected function orderStringLetters($string)
    {
        $letters = str_split($string);
        sort($letters);
        return implode('', $letters);
    }
}
