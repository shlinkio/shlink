<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;

use function count;
use function Shlinkio\Shlink\Core\ArrayUtils\contains;
use function Shlinkio\Shlink\Core\splitByComma;

final readonly class RealTimeUpdatesOptions
{
    public array $enabledTopics;

    public function __construct(array|null $enabledTopics = null)
    {
        $this->enabledTopics = $enabledTopics ?? Topic::allTopicNames();
    }

    public static function fromEnv(): self
    {
        $enabledTopics = splitByComma(EnvVars::REAL_TIME_UPDATES_TOPICS->loadFromEnv());

        return new self(
            enabledTopics: count($enabledTopics) === 0
                ? Topic::allTopicNames()
                // TODO Validate provided topics are in fact Topic names
                : splitByComma(EnvVars::REAL_TIME_UPDATES_TOPICS->loadFromEnv()),
        );
    }

    public function isTopicEnabled(Topic $topic): bool
    {
        return contains($topic->name, $this->enabledTopics);
    }
}
