<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\FieldBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

return static function (ClassMetadata $metadata): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable('api_key_roles');

    $builder
        ->createField('id', Types::BIGINT)
        ->makePrimaryKey()
        ->generatedValue('IDENTITY')
        ->option('unsigned', true)
        ->build();

    new FieldBuilder($builder, [
        'fieldName' => 'role',
        'type' => Types::STRING,
        'enumType' => Role::class,
    ])
        ->columnName('role_name')
        ->length(255)
        ->nullable(false)
        ->build();

    $builder
        ->createField('meta', Types::JSON)
        ->columnName('meta')
        ->nullable(false)
        ->build();

    $builder
        ->createManyToOne('apiKey', ApiKey::class)
        ->addJoinColumn('api_key_id', 'id', false, false, 'CASCADE')
        ->cascadePersist()
        ->build();

    $builder->addUniqueConstraint(['role_name', 'api_key_id'], 'UQ_role_plus_api_key');
};
