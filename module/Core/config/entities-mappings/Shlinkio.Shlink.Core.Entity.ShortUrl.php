<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;

/** @var $metadata ClassMetadata */
$builder = new ClassMetadataBuilder($metadata);

$builder->setTable('short_urls')
        ->setCustomRepositoryClass(Repository\ShortUrlRepository::class);

$builder->createField('id', Type::BIGINT)
        ->columnName('id')
        ->makePrimaryKey()
        ->generatedValue('IDENTITY')
        ->option('unsigned', true)
        ->build();

$builder->createField('longUrl', Type::STRING)
        ->columnName('original_url')
        ->length(1024)
        ->build();

$builder->createField('shortCode', Type::STRING)
        ->columnName('short_code')
        ->unique()
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

$builder->createField('maxVisits', Type::INTEGER)
        ->columnName('max_visits')
        ->nullable()
        ->build();

$builder->createOneToMany('visits', Entity\Visit::class)
        ->mappedBy('shortUrl')
        ->fetchExtraLazy()
        ->build();

$builder->createManyToMany('tags', Entity\Tag::class)
        ->setJoinTable('short_urls_in_tags')
        ->addInverseJoinColumn('tag_id', 'id', true, false, 'CASCADE')
        ->addJoinColumn('short_url_id', 'id', true, false, 'CASCADE')
        ->build();
