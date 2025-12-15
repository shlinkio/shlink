<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl\Input;

use Shlinkio\Shlink\CLI\Command\ShortUrl\ListShortUrlsCommand;
use Shlinkio\Shlink\CLI\Input\InputUtils;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlsParamsInputFilter;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Output\OutputInterface;

use function array_unique;

/**
 * Input arguments and options for short-url:list command
 * @see ListShortUrlsCommand
 */
final class ShortUrlsParamsInput
{
    #[Option('The first page to list (10 items per page unless "--all" is provided).', shortcut: 'p')]
    public int $page = 1;

    #[Option(
        'Disables pagination and just displays all existing URLs. Caution! If the amount of short URLs is big,this '
        . 'may end up failing due to memory usage.',
    )]
    public bool $all = false;

    #[Option('Only return short URLs older than this date', shortcut: 's')]
    public string|null $startDate = null;

    #[Option('Only return short URLs newer than this date', shortcut: 'e')]
    public string|null $endDate = null;

    #[Option('A query used to filter results by searching for it on the longUrl and shortCode fields', shortcut: 'st')]
    public string|null $searchTerm = null;

    #[Option(
        'Used to filter results by domain. Use ' . Domain::DEFAULT_AUTHORITY . ' keyword to filter by default domain',
        shortcut: 'd',
    )]
    public string|null $domain = null;

    /** @var string[]|null */
    #[Option('A list of tags that short URLs need to include', name: 'tag', shortcut: 't')]
    public array|null $tags = null;

    #[Option('If --tag is provided, returns only short URLs including ALL of them')]
    public bool $tagsAll = false;

    /** @var string[]|null */
    #[Option('A list of tags that short URLs should NOT include', name: 'exclude-tag', shortcut: 'et')]
    public array|null $excludeTags = null;

    #[Option('If --exclude-tag is provided, returns only short URLs not including ANY of them')]
    public bool $excludeTagsAll = false;

    #[Option('Excludes short URLs which reached their max amount of visits')]
    public bool $excludeMaxVisitsReached = false;

    #[Option('Excludes short URLs which have a "validUntil" date in the past')]
    public bool $excludePastValidUntil = false;

    #[Option(
        'Field name to order the list by. Set the dir by optionally passing ASC or DESC after "-": --orderBy=tags-ASC',
        shortcut: 'o',
    )]
    public string|null $orderBy = null;

    #[Option('List only short URLs created by the API key matching provided name', shortcut: 'kn')]
    public string|null $apiKeyName = null;

    #[Option('Whether to display the tags or not')]
    public bool $showTags = false;

    #[Option(
        'Whether to display the domain or not. Those belonging to default domain will have value '
        . '"' . Domain::DEFAULT_AUTHORITY . '"',
    )]
    public bool $showDomain = false;

    #[Option('Whether to display the API key name from which the URL was generated or not', shortcut: 'k')]
    public bool $showApiKey = false;

    public function toArray(OutputInterface $output): array
    {
        $data = [
            ShortUrlsParamsInputFilter::PAGE => $this->page,
            ShortUrlsParamsInputFilter::SEARCH_TERM => $this->searchTerm,
            ShortUrlsParamsInputFilter::DOMAIN => $this->domain,
            ShortUrlsParamsInputFilter::ORDER_BY => $this->orderBy,
            ShortUrlsParamsInputFilter::START_DATE => InputUtils::processDate('start-date', $this->startDate, $output),
            ShortUrlsParamsInputFilter::END_DATE => InputUtils::processDate('end-date', $this->endDate, $output),
            ShortUrlsParamsInputFilter::EXCLUDE_MAX_VISITS_REACHED => $this->excludeMaxVisitsReached,
            ShortUrlsParamsInputFilter::EXCLUDE_PAST_VALID_UNTIL => $this->excludePastValidUntil,
            ShortUrlsParamsInputFilter::API_KEY_NAME => $this->apiKeyName,
        ];

        if ($this->tags !== null) {
            $tagsMode = $this->tagsAll ? TagsMode::ALL : TagsMode::ANY;
            $data[ShortUrlsParamsInputFilter::TAGS_MODE] = $tagsMode->value;
            $data[ShortUrlsParamsInputFilter::TAGS] = array_unique($this->tags);
        }

        if ($this->excludeTags !== null) {
            $excludeTagsMode = $this->excludeTagsAll ? TagsMode::ALL : TagsMode::ANY;
            $data[ShortUrlsParamsInputFilter::EXCLUDE_TAGS_MODE] = $excludeTagsMode->value;
            $data[ShortUrlsParamsInputFilter::EXCLUDE_TAGS] = array_unique($this->excludeTags);
        }

        if ($this->all) {
            $data[ShortUrlsParamsInputFilter::ITEMS_PER_PAGE] = Paginator::ALL_ITEMS;
        }

        return $data;
    }
}
