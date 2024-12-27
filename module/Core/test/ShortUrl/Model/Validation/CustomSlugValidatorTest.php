<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Model\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\CustomSlugValidator;
use stdClass;

class CustomSlugValidatorTest extends TestCase
{
    #[Test]
    public function nullIsValid(): void
    {
        $validator = $this->createValidator();
        self::assertTrue($validator->isValid(null));
    }

    #[Test, DataProvider('provideNonStringValues')]
    public function nonStringValuesAreInvalid(mixed $value): void
    {
        $validator = $this->createValidator();

        self::assertFalse($validator->isValid($value));
        self::assertEquals(['NOT_STRING' => 'Provided value is not a string.'], $validator->getMessages());
    }

    public static function provideNonStringValues(): iterable
    {
        yield [123];
        yield [new stdClass()];
        yield [true];
    }

    #[Test]
    public function slashesAreAllowedWhenMultiSegmentSlugsAreEnabled(): void
    {
        $slugWithSlashes = 'foo/bar/baz';

        self::assertTrue($this->createValidator(multiSegmentSlugsEnabled: true)->isValid($slugWithSlashes));
        self::assertFalse($this->createValidator(multiSegmentSlugsEnabled: false)->isValid($slugWithSlashes));
    }

    #[Test, DataProvider('provideInvalidValues')]
    public function valuesWithReservedCharsAreInvalid(string $value): void
    {
        $validator = $this->createValidator();

        self::assertFalse($validator->isValid($value));
        self::assertEquals(
            ['CONTAINS_URL_CHARACTERS' => 'URL-reserved characters cannot be used in a custom slug.'],
            $validator->getMessages(),
        );
    }

    public static function provideInvalidValues(): iterable
    {
        yield ['port:8080'];
        yield ['foo?bar=baz'];
        yield ['some-thing#foo'];
        yield ['brackets[]'];
        yield ['email@example.com'];
    }

    public function createValidator(bool $multiSegmentSlugsEnabled = false): CustomSlugValidator
    {
        return CustomSlugValidator::forUrlShortenerOptions(
            new UrlShortenerOptions(multiSegmentSlugsEnabled: $multiSegmentSlugsEnabled),
        );
    }
}
