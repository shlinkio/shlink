<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata; // @codingStandardsIgnoreLine

/** @var $metadata ClassMetadata */ // @codingStandardsIgnoreLine
$builder = new ClassMetadataBuilder($metadata);

$builder->setTable('visit_locations');

$builder->createField('id', Type::BIGINT)
        ->columnName('id')
        ->makePrimaryKey()
        ->generatedValue('IDENTITY')
        ->option('unsigned', true)
        ->build();

$columns = [
    'country_code' => 'countryCode',
    'country_name' => 'countryName',
    'region_name' => 'regionName',
    'city_name' => 'cityName',
    'timezone' => 'timezone',
];

foreach ($columns as $columnName => $fieldName) {
    $builder->createField($fieldName, Type::STRING)
            ->columnName($columnName)
            ->nullable()
            ->build();
}

$builder->createField('latitude', Type::FLOAT)
        ->columnName('latitude')
        ->nullable()
        ->build();

$builder->createField('longitude', Type::FLOAT)
        ->columnName('longitude')
        ->nullable()
        ->build();
