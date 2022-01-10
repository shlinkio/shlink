<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('visit_locations', $emConfig));

    $builder->createField('id', Types::BIGINT)
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
        fieldWithUtf8Charset($builder->createField($fieldName, Types::STRING), $emConfig)
                ->columnName($columnName)
                ->nullable()
                ->build();
    }

    $builder->createField('latitude', Types::FLOAT)
            ->columnName('lat')
            ->nullable(false)
            ->build();

    $builder->createField('longitude', Types::FLOAT)
            ->columnName('lon')
            ->nullable(false)
            ->build();

    $builder->createField('isEmpty', Types::BOOLEAN)
            ->columnName('is_empty')
            ->option('default', false)
            ->nullable(false)
            ->build();
};
