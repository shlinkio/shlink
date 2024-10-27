<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('short_urls', $emConfig))
            ->setCustomRepositoryClass(ShortUrl\Repository\ShortUrlRepository::class);

    $builder->createField('id', Types::BIGINT)
            ->columnName('id')
            ->makePrimaryKey()
            ->generatedValue('IDENTITY')
            ->option('unsigned', true)
            ->build();

    fieldWithUtf8Charset($builder->createField('longUrl', Types::TEXT), $emConfig)
            ->columnName('original_url') // Rename to long_url some day? ¯\_(ツ)_/¯
            ->length(2048)
            ->build();

    fieldWithUtf8Charset($builder->createField('shortCode', Types::STRING), $emConfig, 'bin')
            ->columnName('short_code')
            ->length(255)
            ->build();

    $builder->createField('dateCreated', ChronosDateTimeType::CHRONOS_DATETIME)
            ->columnName('date_created')
            ->build();

    $builder->createField('validSince', ChronosDateTimeType::CHRONOS_DATETIME)
            ->columnName('valid_since')
            ->nullable()
            ->build();

    $builder->createField('validUntil', ChronosDateTimeType::CHRONOS_DATETIME)
            ->columnName('valid_until')
            ->nullable()
            ->build();

    $builder->createField('maxVisits', Types::INTEGER)
            ->columnName('max_visits')
            ->nullable()
            ->build();

    $builder->createField('importSource', Types::STRING)
            ->columnName('import_source')
            ->nullable()
            ->build();

    fieldWithUtf8Charset($builder->createField('importOriginalShortCode', Types::STRING), $emConfig)
            ->columnName('import_original_short_code')
            ->nullable()
            ->build();

    $builder->createOneToMany('visits', Visit\Entity\Visit::class)
            ->mappedBy('shortUrl')
            ->fetchExtraLazy()
            ->build();

    $builder->createOneToMany('visitsCounts', Visit\Entity\ShortUrlVisitsCount::class)
            ->mappedBy('shortUrl')
            ->fetchExtraLazy() // TODO Check if this makes sense
            ->build();

    $builder->createManyToMany('tags', Tag\Entity\Tag::class)
            ->setJoinTable(determineTableName('short_urls_in_tags', $emConfig))
            ->addInverseJoinColumn('tag_id', 'id', onDelete: 'CASCADE')
            ->addJoinColumn('short_url_id', 'id', onDelete: 'CASCADE')
            ->setOrderBy(['name' => 'ASC'])
            ->build();

    $builder->createManyToOne('domain', Domain\Entity\Domain::class)
            ->addJoinColumn('domain_id', 'id', onDelete: 'RESTRICT')
            ->cascadePersist()
            ->build();

    $builder->createManyToOne('authorApiKey', ApiKey::class)
            ->addJoinColumn('author_api_key_id', 'id', onDelete: 'SET NULL')
            ->build();

    $builder->addUniqueConstraint(['short_code', 'domain_id'], 'unique_short_code_plus_domain');

    fieldWithUtf8Charset($builder->createField('title', Types::STRING), $emConfig)
            ->columnName('title')
            ->length(512)
            ->nullable()
            ->build();

    $builder->createField('titleWasAutoResolved', Types::BOOLEAN)
            ->columnName('title_was_auto_resolved')
            ->option('default', false)
            ->build();

    $builder->createField('crawlable', Types::BOOLEAN)
            ->columnName('crawlable')
            ->option('default', false)
            ->build();

    $builder->createField('forwardQuery', Types::BOOLEAN)
            ->columnName('forward_query')
            ->option('default', true)
            ->build();

    $builder->createOneToMany('redirectRules', RedirectRule\Entity\ShortUrlRedirectRule::class)
            ->mappedBy('shortUrl')
            ->fetchExtraLazy()
            ->build();
};
