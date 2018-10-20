<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Logger\Processor;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Logger\Processor\ExceptionWithNewLineProcessor;
use const PHP_EOL;

class ExceptionWithNewLineProcessorTest extends TestCase
{
    /**
     * @var ExceptionWithNewLineProcessor
     */
    private $processor;

    public function setUp()
    {
        $this->processor = new ExceptionWithNewLineProcessor();
    }

    /**
     * @test
     * @dataProvider provideNoPlaceholderRecords
     */
    public function keepsRecordAsIsWhenNoPlaceholderExists(array $record)
    {
        $this->assertSame($record, ($this->processor)($record));
    }

    public function provideNoPlaceholderRecords(): array
    {
        return [
            [['message' => 'Hello World']],
            [['message' => 'Shlink']],
            [['message' => 'Foo bar']],
        ];
    }

    /**
     * @test
     * @dataProvider providePlaceholderRecords
     */
    public function properlyReplacesExceptionPlaceholderAddingNewLine(array $record, array $expected)
    {
        $this->assertEquals($expected, ($this->processor)($record));
    }

    public function providePlaceholderRecords(): array
    {
        return [
            [
                ['message' => 'Hello World with placeholder {e}'],
                ['message' => 'Hello World with placeholder ' . PHP_EOL . '{e}'],
            ],
            [
                ['message' => '{e} Shlink'],
                ['message' => PHP_EOL . '{e} Shlink'],
            ],
            [
                ['message' => 'Foo {e} bar'],
                ['message' => 'Foo ' . PHP_EOL . '{e} bar'],
            ],
        ];
    }
}
