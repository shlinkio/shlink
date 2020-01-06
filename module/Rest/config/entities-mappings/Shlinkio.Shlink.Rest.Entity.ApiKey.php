<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata; // @codingStandardsIgnoreLine
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;

/** @var $metadata ClassMetadata */ // @codingStandardsIgnoreLine
$builder = new ClassMetadataBuilder($metadata);

$builder->setTable('api_keys');

$builder->createField('id', Types::BIGINT)
        ->makePrimaryKey()
        ->generatedValue('IDENTITY')
        ->option('unsigned', true)
        ->build();

$builder->createField('key', Types::STRING)
        ->columnName('`key`')
        ->unique()
        ->build();

$builder->createField('expirationDate', ChronosDateTimeType::CHRONOS_DATETIME)
        ->columnName('expiration_date')
        ->nullable()
        ->build();

$builder->createField('enabled', Types::BOOLEAN)
        ->build();
