<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Config;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Config\GenerateCharsetCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use function implode;
use function sort;
use function str_split;

class GenerateCharsetCommandTest extends TestCase
{
    private CommandTester $commandTester;

    public function setUp(): void
    {
        $command = new GenerateCharsetCommand();
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function charactersAreGeneratedFromDefault()
    {
        $prefix = 'Character set: ';

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        // Both default character set and the new one should have the same length
        $this->assertStringContainsString($prefix, $output);
    }

    protected function orderStringLetters($string)
    {
        $letters = str_split($string);
        sort($letters);
        return implode('', $letters);
    }
}
