<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Command\ShortUrl\Input\ShortUrlsParamsInput;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtils;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlWithDeps;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlListServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_keys;
use function implode;
use function Shlinkio\Shlink\Core\ArrayUtils\map;
use function sprintf;

#[AsCommand(name: ListShortUrlsCommand::NAME, description: 'List all short URLs')]
class ListShortUrlsCommand extends Command
{
    public const string NAME = 'short-url:list';

    public function __construct(
        private readonly ShortUrlListServiceInterface $shortUrlService,
        private readonly ShortUrlDataTransformerInterface $transformer,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        InputInterface $input,
        #[MapInput] ShortUrlsParamsInput $paramsInput,
    ): int {
        $page = $paramsInput->page;
        $data = $paramsInput->toArray($io);

        $columnsMap = $this->resolveColumnsMap($input);
        do {
            $result = $this->renderPage($io, $columnsMap, ShortUrlsParams::fromRawData($data), $paramsInput->all);
            $page++;

            $continue = $result->hasNextPage() && $io->confirm(
                sprintf('Continue with page <options=bold>%s</>?', $page),
                default: false,
            );
        } while ($continue);

        $io->newLine();
        $io->success('Short URLs properly listed');

        return self::SUCCESS;
    }

    /**
     * @param array<string, callable(array $serializedShortUrl, ShortUrl $shortUrl): ?string> $columnsMap
     * @return Paginator<ShortUrlWithDeps>
     */
    private function renderPage(
        OutputInterface $output,
        array $columnsMap,
        ShortUrlsParams $params,
        bool $all,
    ): Paginator {
        $shortUrls = $this->shortUrlService->listShortUrls($params);

        $rows = map([...$shortUrls], function (ShortUrlWithDeps $shortUrl) use ($columnsMap) {
            $serializedShortUrl = $this->transformer->transform($shortUrl);
            return map($columnsMap, fn (callable $call) => $call($serializedShortUrl, $shortUrl->shortUrl));
        });

        ShlinkTable::default($output)->render(
            array_keys($columnsMap),
            $rows,
            $all ? null : PagerfantaUtils::formatCurrentPageMessage($shortUrls, 'Page %s of %s'),
        );

        return $shortUrls;
    }

    /**
     * @return array<string, callable(array $serializedShortUrl, ShortUrl $shortUrl): ?string>
     */
    private function resolveColumnsMap(InputInterface $input): array
    {
        $pickProp = static fn (string $prop): callable => static fn (array $shortUrl) => $shortUrl[$prop];
        $columnsMap = [
            'Short Code' => $pickProp('shortCode'),
            'Title' => $pickProp('title'),
            'Short URL' => $pickProp('shortUrl'),
            'Long URL' => $pickProp('longUrl'),
            'Date created' => $pickProp('dateCreated'),
            'Visits count' => static fn (array $shortUrl) => $shortUrl['visitsSummary']->total,
        ];
        if ($input->getOption('show-tags')) {
            $columnsMap['Tags'] = static fn (array $shortUrl): string => implode(', ', $shortUrl['tags']);
        }
        if ($input->getOption('show-domain')) {
            $columnsMap['Domain'] = static fn (array $_, ShortUrl $shortUrl): string =>
                $shortUrl->getDomain()->authority ?? Domain::DEFAULT_AUTHORITY;
        }
        if ($input->getOption('show-api-key')) {
            $columnsMap['API Key Name'] = static fn (array $_, ShortUrl $shortUrl): string|null =>
                $shortUrl->authorApiKey?->name;
        }

        return $columnsMap;
    }
}
