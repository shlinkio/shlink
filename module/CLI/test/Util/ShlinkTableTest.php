<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Util;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

class ShlinkTableTest extends TestCase
{
    #[Test]
    public function renderMakesTableToBeRenderedWithProvidedInfo(): void
    {
        $headers = [];
        $rows = [[]];
        $headerTitle = 'Header';
        $footerTitle = 'Footer';

        $baseTable = $this->createMock(Table::class);
        $baseTable->expects($this->once())->method('setStyle')->with(
            $this->isInstanceOf(TableStyle::class),
        )->willReturnSelf();
        $baseTable->expects($this->once())->method('setHeaders')->with($headers)->willReturnSelf();
        $baseTable->expects($this->once())->method('setRows')->with($rows)->willReturnSelf();
        $baseTable->expects($this->once())->method('setFooterTitle')->with($footerTitle)->willReturnSelf();
        $baseTable->expects($this->once())->method('setHeaderTitle')->with($headerTitle)->willReturnSelf();
        $baseTable->expects($this->once())->method('render')->with()->willReturnSelf();

        ShlinkTable::fromBaseTable($baseTable)->render($headers, $rows, $footerTitle, $headerTitle);
    }

    #[Test]
    public function newTableIsCreatedForFactoryMethod(): void
    {
        $instance = ShlinkTable::default($this->createStub(OutputInterface::class));

        $ref = new ReflectionObject($instance);
        $baseTable = $ref->getProperty('baseTable');

        self::assertInstanceOf(Table::class, $baseTable->getValue($instance));
    }
}
