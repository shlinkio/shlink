<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\FieldBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('redirect_conditions', $emConfig));

    $builder->createField('id', Types::BIGINT)
            ->columnName('id')
            ->makePrimaryKey()
            ->generatedValue('IDENTITY')
            ->option('unsigned', true)
            ->build();

    (new FieldBuilder($builder, [
        'fieldName' => 'type',
        'type' => Types::STRING,
        'enumType' => RedirectConditionType::class,
    ]))->columnName('type')
       ->length(255)
       ->build();

    fieldWithUtf8Charset($builder->createField('matchKey', Types::STRING), $emConfig)
        ->columnName('match_key')
        ->length(512)
        ->nullable()
        ->build();

    fieldWithUtf8Charset($builder->createField('matchValue', Types::STRING), $emConfig)
        ->columnName('match_value')
        ->length(512)
        ->build();
};
