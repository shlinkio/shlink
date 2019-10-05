<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata; // @codingStandardsIgnoreLine
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;
use Shlinkio\Shlink\Core\Model\Visitor;

/** @var $metadata ClassMetadata */ // @codingStandardsIgnoreLine
$builder = new ClassMetadataBuilder($metadata);

$builder->setTable('visits')
        ->setCustomRepositoryClass(Repository\VisitRepository::class);

$builder->createField('id', Type::BIGINT)
        ->columnName('id')
        ->makePrimaryKey()
        ->generatedValue('IDENTITY')
        ->option('unsigned', true)
        ->build();

$builder->createField('referer', Type::STRING)
        ->nullable()
        ->length(Visitor::REFERER_MAX_LENGTH)
        ->build();

$builder->createField('date', ChronosDateTimeType::CHRONOS_DATETIME)
        ->columnName('`date`')
        ->build();

$builder->createField('remoteAddr', Type::STRING)
        ->columnName('remote_addr')
        ->length(Visitor::REMOTE_ADDRESS_MAX_LENGTH)
        ->nullable()
        ->build();

$builder->createField('userAgent', Type::STRING)
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
