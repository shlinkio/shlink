<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;
use Shlinkio\Shlink\Rest\Entity\ApiKeyRole;

use function Shlinkio\Shlink\Core\determineTableName;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('api_keys', $emConfig));

    $builder->createField('id', Types::BIGINT)
            ->makePrimaryKey()
            ->generatedValue('IDENTITY')
            ->option('unsigned', true)
            ->build();

    $builder->createField('key', Types::STRING)
            ->columnName('`key`')
            ->unique()
            ->build();

    $builder->createField('name', Types::STRING)
            ->columnName('`name`')
            ->nullable()
            ->build();

    $builder->createField('expirationDate', ChronosDateTimeType::CHRONOS_DATETIME)
            ->columnName('expiration_date')
            ->nullable()
            ->build();

    $builder->createField('enabled', Types::BOOLEAN)
            ->build();

    $builder->createOneToMany('roles', ApiKeyRole::class)
            ->mappedBy('apiKey')
            ->setIndexBy('roleName')
            ->cascadePersist()
            ->orphanRemoval()
            ->build();
};
