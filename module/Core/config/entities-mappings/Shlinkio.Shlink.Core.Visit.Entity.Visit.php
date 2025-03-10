<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\FieldBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('visits', $emConfig))
            ->setCustomRepositoryClass(Visit\Repository\VisitRepository::class);

    $builder->createField('id', Types::BIGINT)
            ->columnName('id')
            ->makePrimaryKey()
            ->generatedValue('IDENTITY')
            ->option('unsigned', true)
            ->build();

    fieldWithUtf8Charset($builder->createField('referer', Types::STRING), $emConfig)
            ->nullable()
            ->length(Visitor::REFERER_MAX_LENGTH)
            ->build();

    $builder->createField('date', ChronosDateTimeType::CHRONOS_DATETIME)
            ->columnName('`date`')
            ->build();

    $builder->addIndex(['date'], 'IDX_visits_date');

    $builder->createField('remoteAddr', Types::STRING)
            ->columnName('remote_addr')
            ->length(Visitor::REMOTE_ADDRESS_MAX_LENGTH)
            ->nullable()
            ->build();

    fieldWithUtf8Charset($builder->createField('userAgent', Types::STRING), $emConfig)
            ->columnName('user_agent')
            ->length(Visitor::USER_AGENT_MAX_LENGTH)
            ->nullable()
            ->build();

    $builder->createManyToOne('shortUrl', ShortUrl\Entity\ShortUrl::class)
            ->addJoinColumn('short_url_id', 'id', onDelete: 'CASCADE')
            ->build();

    $builder->createManyToOne('visitLocation', Visit\Entity\VisitLocation::class)
            ->addJoinColumn('visit_location_id', 'id', onDelete: 'Set NULL')
            ->cascadePersist()
            ->build();

    fieldWithUtf8Charset($builder->createField('visitedUrl', Types::STRING), $emConfig)
            ->columnName('visited_url')
            ->length(Visitor::VISITED_URL_MAX_LENGTH)
            ->nullable()
            ->build();

    (new FieldBuilder($builder, [
        'fieldName' => 'type',
        'type' => Types::STRING,
        'enumType' => VisitType::class,
    ]))->columnName('type')
       ->length(255)
       ->build();

    $builder->createField('potentialBot', Types::BOOLEAN)
            ->columnName('potential_bot')
            ->option('default', false)
            ->build();

    fieldWithUtf8Charset($builder->createField('redirectUrl', Types::STRING), $emConfig)
        ->columnName('redirect_url')
        ->length(Visitor::REDIRECT_URL_MAX_LENGTH)
        ->nullable()
        ->build();
};
