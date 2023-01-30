<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Functional\reduce_left;

final class DataImportCommand extends Command
{
    public const NAME = 'data:import';

    private const SHORT_URL_BATCH_SIZE = 250;
    private const VISITS_BATCH_SIZE = 500;
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
            ->addArgument('startFromCode', InputArgument::OPTIONAL, 'Import will start from this short code')
            ->addArgument('delimiter', InputArgument::OPTIONAL, 'CSV delimiter', self::DEFAULT_DELIMITER);
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
        $domain = $this->getDomain($domainName);
        $apiKeyId = $this->em->getRepository(ApiKey::class)->findOneBy([
            'enabled' => true,
        ])?->getId();
        if (!$apiKeyId) {
            $this->io->error('There is no active API key');
            return ExitCodes::EXIT_FAILURE;
        }

        $shortUrlInserts = [];
        $shortUrlWithClicks = [];
        $tags = [];
        try {
            $iteration = 0;
            foreach ($this->records($file, $input) as $shortUrlData) {
                if ($startFromShortCode && $shortUrlData->shortCode !== $startFromShortCode) {
                    continue;
                }

                ++$iteration;
                $this->io->writeln([
                    sprintf('Iteration: <info>%d</info>', $iteration),
                    sprintf('Short code: <info>%s</info>', $shortUrlData->shortCode),
                    sprintf('Clicks: <info>%s</info>' . PHP_EOL, $shortUrlData->visitsCount),
                ]);

                $shortUrlInserts[] = [
                    'domain_id' => $domain->getId(),
                    'author_api_key_id' => $apiKeyId,
                    'original_url' => $shortUrlData->longUrl,
                    'short_code' => $shortUrlData->shortCode,
                    'date_created' => $shortUrlData->createdAt->format('Y-m-d H:i:s'),
                    'import_source' => $shortUrlData->source->value,
                    'import_original_short_code' => $shortUrlData->shortCode,
                ];

                if ($shortUrlData->tags) {
                    $tags[$shortUrlData->shortCode] = $shortUrlData->tags;
                }
                if ($shortUrlData->visitsCount) {
                    $shortUrlWithClicks[$shortUrlData->shortCode] = $shortUrlData;
                }

                if ($iteration % self::SHORT_URL_BATCH_SIZE) {
                    continue;
                }

                $this->processData($shortUrlInserts, $tags, $shortUrlWithClicks, $domain);
                $shortUrlInserts = [];
                $tags = [];
                $shortUrlWithClicks = [];
            }

            if ($shortUrlInserts) {
                $this->processData($shortUrlInserts, $tags, $shortUrlWithClicks, $domain);
            }
        } catch (\Throwable $e) {
            $this->io->error([
                sprintf('Message: %s', $e->getMessage()),
                sprintf('First short code in the batch: %s', $shortUrlInserts[0]['short_code'] ?? ''),
                sprintf('Last short code in the batch: %s', $shortUrlInserts[(int) array_key_last($shortUrlInserts)]['short_code'] ?? ''),
            ]);
            return ExitCodes::EXIT_FAILURE;
        }
        return ExitCodes::EXIT_SUCCESS;
    }

    private function processData(array $inserts, array $tags, array $shortUrlWithClicks, Domain $domain)
    {
        $this->io->info('Batch processing...');
        $this->em->getConnection()->beginTransaction();
        try {
            $this->runBatchInserts('short_urls', $inserts);
            $this->io->info('Short urls processed - ' . count($inserts));

            $this->processTags($tags, $domain);
            $this->io->info('Tags processed - ' . count($tags));

            $this->processClicks($shortUrlWithClicks, $domain);
            $this->io->info('Visits processed');

            $this->em->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }

        $this->em->clear();
    }

    private function processTags(array $tagsListing, Domain $domain)
    {
        foreach ($tagsListing as $shortCode => $tags) {
            $shortUrl = $this->getShortUrl($shortCode, (int) $domain->getId());
            $tagEntities = new ArrayCollection(array_map(function (string $tag) {
                $tagEntity = $this->em->getRepository(Tag::class)->findOneBy(['name' => $tag]);
                if (!$tagEntity) {
                    $tagEntity = new Tag($tag);
                    $this->em->persist($tagEntity);
                    $this->em->flush();
                }
                return $tagEntity;
            }, $tags));
            $shortUrl->setTags($tagEntities);
            $this->em->persist($shortUrl);
        }
        $this->em->flush();
        $this->em->clear();
    }

    private function processClicks(array $shortUrlWithClicks, Domain $domain): void
    {
        $visitInserts = [];
        /**
         * @var string $shortCode
         * @var ImportedShlinkUrl $dto
         */
        foreach ($shortUrlWithClicks as $shortCode => $dto) {
            $shortUrl = $this->getShortUrl($shortCode, (int) $domain->getId());
            for ($i = 0; $i < $dto->visitsCount; $i++) {
                $visitInserts[] = [
                    'short_url_id' => $shortUrl->getId(),
                    'referer' => '',
                    'date' => $shortUrl->getDateCreated()->format('Y-m-d H:i:s'),
                    'remote_addr' => '',
                    'user_agent' => '',
                    'visited_url' => 'https://' . $domain->getAuthority() . '/' .  $shortUrl->getShortCode(),
                    'type' => VisitType::VALID_SHORT_URL->value,
                ];
                if (count($visitInserts) % self::VISITS_BATCH_SIZE) {
                    continue;
                }
                $this->runBatchInserts('visits', $visitInserts);
                $visitInserts = [];
            }
        }
        if ($visitInserts) {
            $this->runBatchInserts('visits', $visitInserts);
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
    }

    private function getDomain(string $domainName): Domain
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
        return $domain;
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
        $domain = $input->getArgument('domain');

        $csvReader = Reader::createFromStream($file)
            ->setDelimiter($delimiter)
            ->setHeaderOffset(0);

        foreach ($csvReader as $record) {
            $record = $this->remapRecordHeaders($record);

            // TODO "," ?
            $tags = array_filter(explode('|', (string) $record['tags']), fn (string $tag) => trim($tag));

            yield new ImportedShlinkUrl(
                ImportSource::CSV,
                $record['long_url'],
                $tags,
                new \DateTimeImmutable(),
                $domain,
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

    private function getShortUrl(string $shortCode, int $domainId): ShortUrl
    {
        $shortUrl = $this->em->getRepository(ShortUrl::class)->findOneBy([
            'shortCode' => $shortCode,
            'domain' => $domainId,
        ]);
        if (!$shortUrl) {
            throw new \Exception(sprintf('ShortURL was not found. Code: %s', $shortCode));
        }
        return $shortUrl;
    }
}
