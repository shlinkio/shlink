<?php

namespace Config\Options;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\RealTimeUpdatesOptions;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Shlinkio\Shlink\Core\Exception\ValidationException;

class RealTimeUpdatesOptionsTest extends TestCase
{
    #[Test]
    #[TestWith([null, ['NEW_VISIT', 'NEW_SHORT_URL_VISIT', 'NEW_ORPHAN_VISIT', 'NEW_SHORT_URL']])]
    #[TestWith([['NEW_VISIT'], ['NEW_VISIT']])]
    #[TestWith([['NEW_SHORT_URL_VISIT', 'NEW_ORPHAN_VISIT'], ['NEW_SHORT_URL_VISIT', 'NEW_ORPHAN_VISIT']])]
    public function expectedTopicsAreResolved(array|null $providedTopics, array $expectedTopics): void
    {
        $options = new RealTimeUpdatesOptions($providedTopics);
        self::assertEquals($expectedTopics, $options->enabledTopics);
    }

    #[Test]
    public function exceptionIsThrownIfAnyProvidedTopicIsInvalid(): void
    {
        $this->expectException(ValidationException::class);
        new RealTimeUpdatesOptions(['NEW_SHORT_URL_VISIT', 'invalid']);
    }

    #[Test]
    public function checkingIfTopicIsEnabledWorks(): void
    {
        $options = new RealTimeUpdatesOptions(['NEW_ORPHAN_VISIT', 'NEW_SHORT_URL']);

        self::assertTrue($options->isTopicEnabled(Topic::NEW_ORPHAN_VISIT));
        self::assertTrue($options->isTopicEnabled(Topic::NEW_SHORT_URL));
        self::assertFalse($options->isTopicEnabled(Topic::NEW_VISIT));
        self::assertFalse($options->isTopicEnabled(Topic::NEW_SHORT_URL_VISIT));
    }
}
