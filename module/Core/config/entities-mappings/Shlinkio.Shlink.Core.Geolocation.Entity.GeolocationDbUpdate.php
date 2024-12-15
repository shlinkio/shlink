<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\FieldBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;
use Shlinkio\Shlink\Core\Geolocation\Entity\GeolocationDbUpdateStatus;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('geolocation_db_updates', $emConfig));

    $builder->createField('id', Types::BIGINT)
            ->columnName('id')
            ->makePrimaryKey()
            ->generatedValue('IDENTITY')
            ->option('unsigned', true)
            ->build();

    $builder->createField('dateCreated', ChronosDateTimeType::CHRONOS_DATETIME)
            ->columnName('date_created')
            ->build();

    $builder->createField('dateUpdated', ChronosDateTimeType::CHRONOS_DATETIME)
            ->columnName('date_updated')
            ->nullable()
            ->build();

    (new FieldBuilder($builder, [
        'fieldName' => 'status',
        'type' => Types::STRING,
        'enumType' => GeolocationDbUpdateStatus::class,
    ]))->columnName('status')
       ->length(128)
       ->build();

    fieldWithUtf8Charset($builder->createField('error', Types::STRING), $emConfig)
        ->columnName('error')
        ->length(1024)
        ->nullable()
        ->build();

    fieldWithUtf8Charset($builder->createField('filesystemId', Types::STRING), $emConfig)
        ->columnName('filesystem_id')
        ->length(512)
        ->build();

    // Index on date_updated, as we'll usually sort the query by this field
    $builder->addIndex(['date_updated'], 'IDX_geolocation_date_updated');
    // Index on filesystem_id, as we'll usually filter the query by this field
    $builder->addIndex(['filesystem_id'], 'IDX_geolocation_status_filesystem');
};
