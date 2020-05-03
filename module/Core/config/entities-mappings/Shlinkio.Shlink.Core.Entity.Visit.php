<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;
use Shlinkio\Shlink\Core\Model\Visitor;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('visits', $emConfig))
            ->setCustomRepositoryClass(Repository\VisitRepository::class);

    $builder->createField('id', Types::BIGINT)
            ->columnName('id')
            ->makePrimaryKey()
            ->generatedValue('IDENTITY')
            ->option('unsigned', true)
            ->build();

    $builder->createField('referer', Types::STRING)
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

    $builder->createField('userAgent', Types::STRING)
            ->columnName('user_agent')
            ->length(Visitor::USER_AGENT_MAX_LENGTH)
            ->nullable()
            ->build();

    $builder->createManyToOne('shortUrl', Entity\ShortUrl::class)
            ->addJoinColumn('short_url_id', 'id', false, false, 'CASCADE')
            ->build();

    $builder->createManyToOne('visitLocation', Entity\VisitLocation::class)
            ->addJoinColumn('visit_location_id', 'id', true, false, 'Set NULL')
            ->cascadePersist()
            ->build();
};
