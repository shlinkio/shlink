<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Util;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

class ShlinkTableTest extends TestCase
{
    private ShlinkTable $shlinkTable;
    private MockObject & Table $baseTable;

    protected function setUp(): void
    {
        $this->baseTable = $this->createMock(Table::class);
        $this->shlinkTable = ShlinkTable::fromBaseTable($this->baseTable);
    }

    #[Test]
    public function renderMakesTableToBeRenderedWithProvidedInfo(): void
    {
        $headers = [];
        $rows = [[]];
        $headerTitle = 'Header';
        $footerTitle = 'Footer';

        $this->baseTable->expects($this->once())->method('setStyle')->with(
            $this->isInstanceOf(TableStyle::class),
        )->willReturnSelf();
        $this->baseTable->expects($this->once())->method('setHeaders')->with($headers)->willReturnSelf();
        $this->baseTable->expects($this->once())->method('setRows')->with($rows)->willReturnSelf();
        $this->baseTable->expects($this->once())->method('setFooterTitle')->with($footerTitle)->willReturnSelf();
        $this->baseTable->expects($this->once())->method('setHeaderTitle')->with($headerTitle)->willReturnSelf();
        $this->baseTable->expects($this->once())->method('render')->with()->willReturnSelf();

        $this->shlinkTable->render($headers, $rows, $footerTitle, $headerTitle);
    }

    #[Test]
    public function newTableIsCreatedForFactoryMethod(): void
    {
        $instance = ShlinkTable::default($this->createMock(OutputInterface::class));

        $ref = new ReflectionObject($instance);
        $baseTable = $ref->getProperty('baseTable');

        self::assertInstanceOf(Table::class, $baseTable->getValue($instance));
    }
}
