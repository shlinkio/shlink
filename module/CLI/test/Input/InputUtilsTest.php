<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Input;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Input\InputUtils;
use Symfony\Component\Console\Output\OutputInterface;

class InputUtilsTest extends TestCase
{
    private MockObject & OutputInterface $input;

    protected function setUp(): void
    {
        $this->input = $this->createMock(OutputInterface::class);
    }

    #[Test]
    #[TestWith([null], 'null')]
    #[TestWith([''], 'empty string')]
    public function processDateReturnsNullForEmptyDates(string|null $date): void
    {
        self::assertNull(InputUtils::processDate('name', $date, $this->input));
    }

    #[Test]
    public function processDateReturnsAtomFormatedForValidDates(): void
    {
        $date = '2025-01-20';
        self::assertEquals(Chronos::parse($date)->toAtomString(), InputUtils::processDate('name', $date, $this->input));
    }

    #[Test]
    public function warningIsPrintedWhenDateIsInvalid(): void
    {
        $this->input->expects($this->once())->method('writeln')->with(
            '<comment>> Ignored provided "name" since its value "invalid" is not a valid date. <</comment>',
        );
        self::assertNull(InputUtils::processDate('name', 'invalid', $this->input));
    }
}
