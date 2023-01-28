<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\FieldBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shlinkio\Shlink\Core\Model\DeviceType;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('device_long_urls', $emConfig));

    $builder->createField('id', Types::BIGINT)
            ->columnName('id')
            ->makePrimaryKey()
            ->generatedValue('IDENTITY')
            ->option('unsigned', true)
            ->build();

    (new FieldBuilder($builder, [
        'fieldName' => 'deviceType',
        'type' => Types::STRING,
        'enumType' => DeviceType::class,
    ]))->columnName('device_type')
       ->length(255)
       ->build();

    fieldWithUtf8Charset($builder->createField('longUrl', Types::STRING), $emConfig)
        ->columnName('long_url')
        ->length(2048)
        ->build();

    $builder->createManyToOne('shortUrl', ShortUrl\Entity\ShortUrl::class)
            ->addJoinColumn('short_url_id', 'id', false, false, 'CASCADE')
            ->build();
};
