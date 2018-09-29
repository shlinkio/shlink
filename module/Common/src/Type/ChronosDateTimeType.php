<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Type;

use Cake\Chronos\Chronos;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeImmutableType;

class ChronosDateTimeType extends DateTimeImmutableType
{
    public const CHRONOS_DATETIME = 'chronos_datetime';

    public function getName(): string
    {
        return self::CHRONOS_DATETIME;
    }

    /**
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Chronos
    {
        if ($value === null) {
            return null;
        }

        $dateTime = parent::convertToPHPValue($value, $platform);
        return Chronos::instance($dateTime);
    }

    /**
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($platform->getDateTimeFormatString());
        }

        throw ConversionException::conversionFailedInvalidType(
            $value,
            $this->getName(),
            ['null', \DateTimeInterface::class]
        );
    }
}
