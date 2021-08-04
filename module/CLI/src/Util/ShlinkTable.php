<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Util;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;

use function Functional\intersperse;

final class ShlinkTable
{
    private const DEFAULT_STYLE_NAME = 'default';
    private const TABLE_TITLE_STYLE = '<options=bold> %s </>';

    private function __construct(private Table $baseTable, private bool $withRowSeparators)
    {
    }

    public static function default(OutputInterface $output): self
    {
        return new self(new Table($output), false);
    }

    public static function withRowSeparators(OutputInterface $output): self
    {
        return new self(new Table($output), true);
    }

    public static function fromBaseTable(Table $baseTable): self
    {
        return new self($baseTable, false);
    }

    public function render(array $headers, array $rows, ?string $footerTitle = null, ?string $headerTitle = null): void
    {
        $style = Table::getStyleDefinition(self::DEFAULT_STYLE_NAME);
        $style->setFooterTitleFormat(self::TABLE_TITLE_STYLE)
              ->setHeaderTitleFormat(self::TABLE_TITLE_STYLE);
        $tableRows = $this->withRowSeparators ? intersperse($rows, new TableSeparator()) : $rows;

        $table = clone $this->baseTable;
        $table->setStyle($style)
              ->setHeaders($headers)
              ->setRows($tableRows)
              ->setFooterTitle($footerTitle)
              ->setHeaderTitle($headerTitle)
              ->render();
    }
}
