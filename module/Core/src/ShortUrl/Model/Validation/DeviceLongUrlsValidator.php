<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model\Validation;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\ValidatorInterface;
use Shlinkio\Shlink\Core\Model\DeviceType;

use function array_keys;
use function array_values;
use function Functional\contains;
use function Functional\every;
use function is_array;
use function Shlinkio\Shlink\Core\enumValues;

class DeviceLongUrlsValidator extends AbstractValidator
{
    private const NOT_ARRAY = 'NOT_ARRAY';
    private const INVALID_DEVICE = 'INVALID_DEVICE';
    private const INVALID_LONG_URL = 'INVALID_LONG_URL';

    protected array $messageTemplates = [
        self::NOT_ARRAY => 'Provided value is not an array.',
        self::INVALID_DEVICE => 'You have provided at least one invalid device identifier.',
        self::INVALID_LONG_URL => 'At least one of the long URLs are invalid.',
    ];

    public function __construct(private readonly ValidatorInterface $longUrlValidators)
    {
        parent::__construct();
    }

    public function isValid(mixed $value): bool
    {
        if (! is_array($value)) {
            $this->error(self::NOT_ARRAY);
            return false;
        }

        $validValues = enumValues(DeviceType::class);
        $keys = array_keys($value);
        if (! every($keys, static fn ($key) => contains($validValues, $key))) {
            $this->error(self::INVALID_DEVICE);
            return false;
        }

        $longUrls = array_values($value);
        $result = every($longUrls, $this->longUrlValidators->isValid(...));
        if (! $result) {
            $this->error(self::INVALID_LONG_URL);
        }

        return $result;
    }
}
