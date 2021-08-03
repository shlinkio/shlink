<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Util;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionObject;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

class ShlinkTableTest extends TestCase
{
    use ProphecyTrait;

    private ShlinkTable $shlinkTable;
    private ObjectProphecy $baseTable;

    public function setUp(): void
    {
        $this->baseTable = $this->prophesize(Table::class);
        $this->shlinkTable = ShlinkTable::fromBaseTable($this->baseTable->reveal());
    }

    /** @test */
    public function renderMakesTableToBeRenderedWithProvidedInfo(): void
    {
        $headers = [];
        $rows = [[]];
        $headerTitle = 'Header';
        $footerTitle = 'Footer';

        $setStyle = $this->baseTable->setStyle(Argument::type(TableStyle::class))->willReturn(
            $this->baseTable->reveal(),
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

    /** @test */
    public function newTableIsCreatedForFactoryMethod(): void
    {
        $instance = ShlinkTable::default($this->prophesize(OutputInterface::class)->reveal());

        $ref = new ReflectionObject($instance);
        $baseTable = $ref->getProperty('baseTable');
        $baseTable->setAccessible(true);

        self::assertInstanceOf(Table::class, $baseTable->getValue($instance));
    }
}
