<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Doctrine\Type;

use Cake\Chronos\Chronos;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;
use stdClass;

class ChronosDateTimeTypeTest extends TestCase
{
    /** @var ChronosDateTimeType */
    private $type;

    public function setUp(): void
    {
        if (! Type::hasType(ChronosDateTimeType::CHRONOS_DATETIME)) {
            Type::addType(ChronosDateTimeType::CHRONOS_DATETIME, ChronosDateTimeType::class);
        }

        $this->type = Type::getType(ChronosDateTimeType::CHRONOS_DATETIME);
    }

    /** @test */
    public function nameIsReturned(): void
    {
        $this->assertEquals(ChronosDateTimeType::CHRONOS_DATETIME, $this->type->getName());
    }

    /**
     * @test
     * @dataProvider provideValues
     */
    public function valueIsConverted(?string $value, ?string $expected): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        $platform->getDateTimeFormatString()->willReturn('Y-m-d H:i:s');

        $result = $this->type->convertToPHPValue($value, $platform->reveal());

        if ($expected === null) {
            $this->assertNull($result);
        } else {
            $this->assertInstanceOf($expected, $result);
        }
    }

    public function provideValues(): iterable
    {
        yield 'null date' => [null, null];
        yield 'human friendly date' => ['now', Chronos::class];
        yield 'numeric date' => ['2017-01-01', Chronos::class];
    }

    /**
     * @test
     * @dataProvider providePhpValues
     */
    public function valueIsConvertedToDatabaseFormat(?DateTimeInterface $value, ?string $expected): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        $platform->getDateTimeFormatString()->willReturn('Y-m-d');

        $this->assertEquals($expected, $this->type->convertToDatabaseValue($value, $platform->reveal()));
    }

    public function providePhpValues(): iterable
    {
        yield 'null date' => [null, null];
        yield 'DateTimeImmutable date' => [new DateTimeImmutable('2017-01-01'), '2017-01-01'];
        yield 'Chronos date' => [Chronos::parse('2017-02-01'), '2017-02-01'];
        yield 'DateTime date' => [new DateTime('2017-03-01'), '2017-03-01'];
    }

    /** @test */
    public function exceptionIsThrownIfInvalidValueIsParsedToDatabase(): void
    {
        $this->expectException(ConversionException::class);
        $this->type->convertToDatabaseValue(new stdClass(), $this->prophesize(AbstractPlatform::class)->reveal());
    }
}
