<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Console;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionObject;
use Shlinkio\Shlink\Common\Console\ShlinkTable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

class ShlinkTableTest extends TestCase
{
    /** @var ShlinkTable */
    private $shlinkTable;
    /** @var ObjectProphecy */
    private $baseTable;

    public function setUp()
    {
        $this->baseTable = $this->prophesize(Table::class);
        $this->shlinkTable = new ShlinkTable($this->baseTable->reveal());
    }

    /**
     * @test
     */
    public function renderMakesTableToBeRenderedWithProvidedInfo()
    {
        $headers = [];
        $rows = [[]];
        $headerTitle = 'Header';
        $footerTitle = 'Footer';

        $setStyle = $this->baseTable->setStyle(Argument::type(TableStyle::class))->willReturn(
            $this->baseTable->reveal()
        );
        $setHeaders = $this->baseTable->setHeaders($headers)->willReturn($this->baseTable->reveal());
        $setRows = $this->baseTable->setRows($rows)->willReturn($this->baseTable->reveal());
        $setFooterTitle = $this->baseTable->setFooterTitle($footerTitle)->willReturn($this->baseTable->reveal());
        $setHeaderTitle = $this->baseTable->setHeaderTitle($headerTitle)->willReturn($this->baseTable->reveal());
        $render = $this->baseTable->render()->willReturn($this->baseTable->reveal());

        $this->shlinkTable->render($headers, $rows, $footerTitle, $headerTitle);

        $setStyle->shouldHaveBeenCalledOnce();
        $setHeaders->shouldHaveBeenCalledOnce();
        $setRows->shouldHaveBeenCalledOnce();
        $setFooterTitle->shouldHaveBeenCalledOnce();
        $setHeaderTitle->shouldHaveBeenCalledOnce();
        $render->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function newTableIsCreatedForFactoryMethod()
    {
        $instance = ShlinkTable::fromOutput($this->prophesize(OutputInterface::class)->reveal());

        $ref = new ReflectionObject($instance);
        $baseTable = $ref->getProperty('baseTable');
        $baseTable->setAccessible(true);

        $this->assertInstanceOf(Table::class, $baseTable->getValue($instance));
    }
}
