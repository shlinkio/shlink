<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Collection;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Collection\PathCollection;

class PathCollectionTest extends TestCase
{
    /**
     * @var PathCollection
     */
    private $collection;

    public function setUp()
    {
        $this->collection = new PathCollection([
            'foo' => [
                'bar' => [
                    'baz' => 'Hello world!',
                ],
            ],
            'something' => [],
            'another' => [
                'one' => 'Shlink',
            ],
        ]);
    }

    /**
     * @test
     * @dataProvider providePaths
     */
    public function pathExistsReturnsExpectedValue(array $path, bool $expected)
    {
        $this->assertEquals($expected, $this->collection->pathExists($path));
    }

    public function providePaths(): array
    {
        return [
            [[], false],
            [['boo'], false],
            [['foo', 'nop'], false],
            [['another', 'one', 'nop'], false],
            [['foo'], true],
            [['foo', 'bar'], true],
            [['foo', 'bar', 'baz'], true],
            [['something'], true],
        ];
    }

    /**
     * @test
     * @dataProvider providePathsWithValue
     */
    public function getValueInPathReturnsExpectedValue(array $path, $expected)
    {
        $this->assertEquals($expected, $this->collection->getValueInPath($path));
    }

    public function providePathsWithValue(): array
    {
        return [
            [[], null],
            [['boo'], null],
            [['foo', 'nop'], null],
            [['another', 'one', 'nop'], null],
            [['foo'], [
                'bar' => [
                    'baz' => 'Hello world!',
                ],
            ]],
            [['foo', 'bar'], [
                'baz' => 'Hello world!',
            ]],
            [['foo', 'bar', 'baz'], 'Hello world!'],
            [['something'], []],
        ];
    }
}
