<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Factory;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Installer\Factory\ProcessHelperFactory;

class ProcessHelperFactoryTest extends TestCase
{
    /** @var ProcessHelperFactory */
    private $factory;

    public function setUp(): void
    {
        $this->factory = new ProcessHelperFactory();
    }

    /** @test */
    public function createsTheServiceWithTheProperSetOfHelpers(): void
    {
        $processHelper = ($this->factory)();
        $helperSet = $processHelper->getHelperSet();

        $this->assertCount(2, $helperSet);
        $this->assertTrue($helperSet->has('formatter'));
        $this->assertTrue($helperSet->has('debug_formatter'));
    }
}
