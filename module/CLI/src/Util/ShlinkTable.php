<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Util;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;

use function array_pop;

final class ShlinkTable
{
    private const string DEFAULT_STYLE_NAME = 'default';
    private const string TABLE_TITLE_STYLE = '<options=bold> %s </>';

    private function __construct(private readonly Table $baseTable, private readonly bool $withRowSeparators = false)
    {
    }

    public static function default(OutputInterface $output): self
    {
        return new self(new Table($output));
    }

    public static function withRowSeparators(OutputInterface $output): self
    {
        return new self(new Table($output), withRowSeparators: true);
    }

    public static function fromBaseTable(Table $baseTable): self
    {
        return new self($baseTable);
    }

    public function render(
        array $headers,
        array $rows,
        string|null $footerTitle = null,
        string|null $headerTitle = null,
    ): void {
        $style = Table::getStyleDefinition(self::DEFAULT_STYLE_NAME);
        $style->setFooterTitleFormat(self::TABLE_TITLE_STYLE)
              ->setHeaderTitleFormat(self::TABLE_TITLE_STYLE);
        $tableRows = $this->withRowSeparators ? $this->addRowSeparators($rows) : $rows;

        $table = clone $this->baseTable;
        $table->setStyle($style)
              ->setHeaders($headers)
              ->setRows($tableRows)
              ->setFooterTitle($footerTitle)
              ->setHeaderTitle($headerTitle)
              ->render();
    }

    private function addRowSeparators(array $rows): array
    {
        $aggregation = [];
        $separator = new TableSeparator();

        foreach ($rows as $row) {
            $aggregation[] = $row;
            $aggregation[] = $separator;
        }

        // Remove last separator
        array_pop($aggregation);

        return $aggregation;
    }
}
