<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('domains', $emConfig))
            ->setCustomRepositoryClass(Domain\Repository\DomainRepository::class);

    $builder->createField('id', Types::BIGINT)
            ->columnName('id')
            ->makePrimaryKey()
            ->generatedValue('IDENTITY')
            ->option('unsigned', true)
            ->build();

    fieldWithUtf8Charset($builder->createField('authority', Types::STRING), $emConfig)
            ->unique()
            ->build();

    fieldWithUtf8Charset($builder->createField('baseUrlRedirect', Types::TEXT), $emConfig)
            ->columnName('base_url_redirect')
            ->nullable()
            ->length(2048)
            ->build();

    fieldWithUtf8Charset($builder->createField('regular404Redirect', Types::TEXT), $emConfig)
            ->columnName('regular_not_found_redirect')
            ->nullable()
            ->length(2048)
            ->build();

    fieldWithUtf8Charset($builder->createField('invalidShortUrlRedirect', Types::TEXT), $emConfig)
            ->columnName('invalid_short_url_redirect')
            ->nullable()
            ->length(2048)
            ->build();
};
