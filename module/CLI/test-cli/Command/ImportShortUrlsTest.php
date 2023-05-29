<?php

declare(strict_types=1);

namespace ShlinkioCliTest\Shlink\CLI\Command;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\CLI\Command\ShortUrl\ListShortUrlsCommand;
use Shlinkio\Shlink\Importer\Command\ImportCommand;
use Shlinkio\Shlink\TestUtils\CliTest\CliTestCase;

use function fclose;
use function fopen;
use function fwrite;
use function is_string;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class ImportShortUrlsTest extends CliTestCase
{
    private false|string|null $tempCsvFile = null;

    protected function setUp(): void
    {
        $this->tempCsvFile = tempnam(sys_get_temp_dir(), 'shlink_csv');
        if (! $this->tempCsvFile) {
            return;
        }

        $handle = fopen($this->tempCsvFile, 'w+');
        if (! $handle) {
            $this->fail('It was not possible to open the temporary file to write CSV on it');
        }

        fwrite(
            $handle,
            <<<CSV
            longURL;tags;domain;short code;Title
            https://shlink.io;foo,baz;s.test;testing-default-domain-import-1;
            https://example.com;foo;s.test;testing-default-domain-import-2;
            CSV,
        );
        fclose($handle);
    }

    protected function tearDown(): void
    {
        if (is_string($this->tempCsvFile)) {
            unlink($this->tempCsvFile);
        }
    }

    #[Test]
    public function defaultDomainIsIgnoredWhenExplicitlyProvided(): void
    {
        if (! $this->tempCsvFile) {
            $this->fail('It was not possible to create a temporary CSV file');
        }

        [$output] = $this->exec([ImportCommand::NAME, 'csv'], [$this->tempCsvFile, ';']);

        self::assertStringContainsString('https://shlink.io: Imported', $output);
        self::assertStringContainsString('https://example.com: Imported', $output);

        [$listOutput1] = $this->exec(
            [ListShortUrlsCommand::NAME, '--show-domain', '--search-term', 'testing-default-domain-import-1'],
        );
        self::assertStringContainsString('DEFAULT', $listOutput1);
        [$listOutput1] = $this->exec(
            [ListShortUrlsCommand::NAME, '--show-domain', '--search-term', 'testing-default-domain-import-2'],
        );
        self::assertStringContainsString('DEFAULT', $listOutput1);
    }
}
