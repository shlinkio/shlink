<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command;

use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Functional\reduce_left;

final class ClicksCsvImportCommand extends Command
{
    public const NAME = 'clicks:import-csv';

    private const BATCH_SIZE = 500;
    private const DEFAULT_DELIMITER = ';';

    private ?SymfonyStyle $io = null;

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();

        $connection = $this->em->getConnection();
        $connection->getConfiguration()->setSQLLogger(null);
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Import clicks from CSV file')
            ->addArgument('path', InputArgument::REQUIRED, 'CSV file path')
            ->addArgument('domain', InputArgument::REQUIRED, 'Domain for searching')
            ->addArgument('delimiter', InputArgument::OPTIONAL, 'CSV delimiter', self::DEFAULT_DELIMITER)
            ->addArgument('startFromCode', InputArgument::OPTIONAL, 'Import will start from this short code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initializeIO($input, $output);
        if (!$this->isValidInput($input)) {
            return ExitCodes::EXIT_FAILURE;
        }

        $path = $input->getArgument('path');
        $file = @fopen($path, 'rb');
        if (!$file) {
            $this->io->error('Path is not a reference to file');
            return ExitCodes::EXIT_FAILURE;
        }

        $startFromShortCode = $input->getArgument('startFromCode');
        $domainName = $input->getArgument('domain');
        $domainId = $this->getDomainId($domainName);
        $visitInserts = [];
        try {
            $iteration = 0;
            foreach ($this->records($file, $input) as $shortUrlData) {
                if ($startFromShortCode && $shortUrlData->shortCode !== $startFromShortCode) {
                    continue;
                }
                if (!$shortUrlData->visitsCount) {
                    continue;
                }

                ++$iteration;
                $this->io->writeln([
                    sprintf('Iteration: <info>%d</info>', $iteration),
                    sprintf('Short code: <info>%s</info>', $shortUrlData->shortCode),
                    sprintf('Clicks: <info>%s</info>' . PHP_EOL, $shortUrlData->visitsCount),
                ]);

                $shortUrl = $this->em->getRepository(ShortUrl::class)->findOneBy([
                    'shortCode' => $shortUrlData->shortCode,
                    'domain' => $domainId,
                ]);
                if (!$shortUrl) {
                    $this->io->error(sprintf('ShortURL was not found. Code: %s', $shortUrlData->shortCode));
                    return ExitCodes::EXIT_FAILURE;
                }

                $visitInserts[] = [
                    'short_url_id' => $shortUrl->getId(),
                    'referer' => '',
                    'date' => $shortUrl->getDateCreated()->format('Y-m-d H:i:s'),
                    'remote_addr' => '',
                    'user_agent' => '',
                    'visited_url' => 'https://' . $domainName . '/' .  $shortUrl->getShortCode(),
                    'type' => VisitType::VALID_SHORT_URL->value,
                ];

                if ($iteration % self::BATCH_SIZE) {
                    continue;
                }

                $this->runBatchInserts('visits', $visitInserts);
                $visitInserts = [];
            }

            if ($visitInserts) {
                $this->runBatchInserts('visits', $visitInserts);
            }
            return ExitCodes::EXIT_SUCCESS;
        } catch (\Throwable $e) {
            $this->io->error([
                sprintf('Message: %s', $e->getMessage()),
                sprintf('LastInserts: %s', json_encode($visitInserts)),
            ]);
            return ExitCodes::EXIT_FAILURE;
        }
    }

    /**
     * @param string $tableName
     * @param array $batchInserts
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    private function runBatchInserts(string $tableName, array $batchInserts): void
    {
        $this->io->info('Process batch visits...');

        $columns = implode(',', array_keys($batchInserts[0]));
        $params = [];
        $batchValues = '';
        foreach ($batchInserts as $insertItem) {
            $values = [];
            foreach ($insertItem as $value) {
                $params[] = $value;
                $values[] = '?';
            }
            $batchValues .= ($batchValues ? ',' : '') . '(' . implode(',', $values) . ')';
        }
        $sql = sprintf('INSERT INTO %s (%s) VALUES %s', $tableName, $columns, $batchValues);
        $this->em->getConnection()->executeStatement($sql, $params);

        $this->io->info('Processed');
    }

    private function getDomainId(string $domainName): int
    {
        /** @var DomainRepositoryInterface $repo */
        $repo = $this->em->getRepository(Domain::class);
        $domain = $repo->findOneByAuthority($domainName);
        if (!$domain) {
            $domain = Domain::withAuthority($domainName);
            $this->em->persist($domain);
            $this->em->flush();
            $this->em->clear();
        }
        return (int) $domain->getId();
    }

    /**
     * @param resource $file
     * @param InputInterface $input
     * @return iterable<ImportedShlinkUrl>
     * @throws \League\Csv\Exception
     * @throws \League\Csv\InvalidArgument
     */
    private function records($file, InputInterface $input): iterable
    {
        $delimiter = $input->getArgument('delimiter') ?: self::DEFAULT_DELIMITER;

        $csvReader = Reader::createFromStream($file)
            ->setDelimiter($delimiter)
            ->setHeaderOffset(0);

        foreach ($csvReader as $record) {
            $record = $this->remapRecordHeaders($record);

            yield new ImportedShlinkUrl(
                ImportSource::CSV,
                $record['long_url'],
                [],
                new \DateTimeImmutable(),
                null,
                $record['short_code'],
                $record['title'] ?: null,
                [],
                (int) $record['clicks']
            );
        }
    }

    private function isValidInput(InputInterface $input): bool
    {
        $path = $input->getArgument('path');
        if (!file_exists($path)) {
            $this->io->error('File does not exist. Path: ' . $path);
            return false;
        }
        return true;
    }

    private function initializeIO(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    private function remapRecordHeaders(array $record): array
    {
        return reduce_left($record, static function ($value, string $index, array $c, array $acc) {
            $normalizedKey = strtolower(str_replace(' ', '_', $index));
            $acc[$normalizedKey] = $value;

            return $acc;
        }, []);
    }
}
