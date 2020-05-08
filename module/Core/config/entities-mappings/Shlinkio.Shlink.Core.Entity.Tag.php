<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('tags', $emConfig))
            ->setCustomRepositoryClass(Repository\TagRepository::class);

    $builder->createField('id', Types::BIGINT)
            ->columnName('id')
            ->makePrimaryKey()
            ->generatedValue('IDENTITY')
            ->option('unsigned', true)
            ->build();

    $builder->createField('name', Types::STRING)
            ->unique()
            ->build();

    $builder->createManyToMany('shortUrls', Entity\ShortUrl::class)
            ->setJoinTable(determineTableName('short_urls_in_tags', $emConfig))
            ->addInverseJoinColumn('short_url_id', 'id', true, false, 'CASCADE')
            ->addJoinColumn('tag_id', 'id', true, false, 'CASCADE')
            ->build();
};
