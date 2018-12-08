<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Console;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

final class ShlinkTable
{
    private const DEFAULT_STYLE_NAME = 'default';
    private const TABLE_TITLE_STYLE = '<options=bold> %s </>';

    /** @var Table|null */
    private $baseTable;

    public function __construct(Table $baseTable)
    {
        $this->baseTable = $baseTable;
    }

    public static function fromOutput(OutputInterface $output): self
    {
        return new self(new Table($output));
    }

    public function render(array $headers, array $rows, ?string $headerTitle = null, ?string $footerTitle = null): void
    {
        $style = Table::getStyleDefinition(self::DEFAULT_STYLE_NAME);
        $style->setFooterTitleFormat(self::TABLE_TITLE_STYLE)
              ->setHeaderTitleFormat(self::TABLE_TITLE_STYLE);

        $table = clone $this->baseTable;
        $table->setStyle($style)
              ->setHeaders($headers)
              ->setRows($rows)
              ->setHeaderTitle($headerTitle)
              ->setFooterTitle($footerTitle)
              ->render();
    }
}
