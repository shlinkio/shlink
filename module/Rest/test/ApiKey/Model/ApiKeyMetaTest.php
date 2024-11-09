<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\ApiKey\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;

use function sprintf;
use function substr;

class ApiKeyMetaTest extends TestCase
{
    #[Test, DataProvider('provideNames')]
    public function nameIsInferredWhenNotProvided(string|null $key, string|null $name, callable $getExpectedName): void
    {
        $meta = ApiKeyMeta::fromParams(key: $key, name: $name);
        $expectedName = $getExpectedName($meta);

        self::assertEquals($expectedName, $meta->name);
    }

    public static function provideNames(): iterable
    {
        yield 'name' => [null, 'the name', static fn (ApiKeyMeta $meta) => 'the name'];
        yield 'key' => ['the key', null, static fn (ApiKeyMeta $meta) => 'the key'];
        yield 'generated key' => [null, null, static fn (ApiKeyMeta $meta) => sprintf(
            '%s-****-****-****-************',
            substr($meta->key, offset: 0, length: 8),
        )];
    }
}
