<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Logger\Processor;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Logger\Processor\ExceptionWithNewLineProcessor;
use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use const PHP_EOL;
use function Functional\map;
use function range;

class ExceptionWithNewLineProcessorTest extends TestCase
{
    use StringUtilsTrait;

    /** @var ExceptionWithNewLineProcessor */
    private $processor;

    public function setUp(): void
    {
        $this->processor = new ExceptionWithNewLineProcessor();
    }

    /**
     * @test
     * @dataProvider provideNoPlaceholderRecords
     */
    public function keepsRecordAsIsWhenNoPlaceholderExists(array $record): void
    {
        $this->assertSame($record, ($this->processor)($record));
    }

    public function provideNoPlaceholderRecords(): iterable
    {
        return map(range(1, 5), function () {
            return [['message' => $this->generateRandomString()]];
        });
    }

    /**
     * @test
     * @dataProvider providePlaceholderRecords
     */
    public function properlyReplacesExceptionPlaceholderAddingNewLine(array $record, array $expected): void
    {
        $this->assertEquals($expected, ($this->processor)($record));
    }

    public function providePlaceholderRecords(): iterable
    {
        yield [
            ['message' => 'Hello World with placeholder {e}'],
            ['message' => 'Hello World with placeholder ' . PHP_EOL . '{e}'],
        ];
        yield [
            ['message' => '{e} Shlink'],
            ['message' => PHP_EOL . '{e} Shlink'],
        ];
        yield [
            ['message' => 'Foo {e} bar'],
            ['message' => 'Foo ' . PHP_EOL . '{e} bar'],
        ];
    }
}
