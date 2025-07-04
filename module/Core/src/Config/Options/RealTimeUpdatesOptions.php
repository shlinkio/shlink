<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Shlinkio\Shlink\Core\Exception\ValidationException;

use function count;
use function implode;
use function Shlinkio\Shlink\Core\ArrayUtils\contains;
use function Shlinkio\Shlink\Core\ArrayUtils\map;
use function Shlinkio\Shlink\Core\splitByComma;
use function sprintf;

final readonly class RealTimeUpdatesOptions
{
    /** @var string[] */
    public array $enabledTopics;

    public function __construct(array|null $enabledTopics = null)
    {
        $validTopics = Topic::allTopicNames();
        $this->enabledTopics = $enabledTopics === null ? $validTopics : self::validateTopics(
            $enabledTopics,
            $validTopics,
        );
    }

    public static function fromEnv(): self
    {
        $enabledTopics = splitByComma(EnvVars::REAL_TIME_UPDATES_TOPICS->loadFromEnv());
        return new self(enabledTopics: count($enabledTopics) === 0 ? null : $enabledTopics);
    }

    /**
     * @param string[] $validTopics
     * @return string[]
     */
    private static function validateTopics(array $providedTopics, array $validTopics): array
    {
        return map($providedTopics, function (string $topic) use ($validTopics): string {
            if (contains($topic, $validTopics)) {
                return $topic;
            }

            throw ValidationException::fromArray([
                'topic' => sprintf(
                    'Real-time updates topic "%s" is not valid. Expected one of ["%s"].',
                    $topic,
                    implode('", "', $validTopics),
                ),
            ]);
        });
    }

    public function isTopicEnabled(Topic $topic): bool
    {
        return contains($topic->name, $this->enabledTopics);
    }
}
