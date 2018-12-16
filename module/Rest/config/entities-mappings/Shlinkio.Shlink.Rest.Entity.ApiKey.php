<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shlinkio\Shlink\Common\Type\ChronosDateTimeType;

/** @var $metadata ClassMetadata */
$builder = new ClassMetadataBuilder($metadata);

$builder->setTable('api_keys');

$builder->createField('id', Type::BIGINT)
        ->makePrimaryKey()
        ->generatedValue('IDENTITY')
        ->option('unsigned', true)
        ->build();

$builder->createField('key', Type::STRING)
        ->columnName('`key`')
        ->unique()
        ->build();

$builder->createField('expirationDate', ChronosDateTimeType::CHRONOS_DATETIME)
        ->columnName('expiration_date')
        ->nullable()
        ->build();

$builder->createField('enabled', Type::BOOLEAN)
        ->build();
