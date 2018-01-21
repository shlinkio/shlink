<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

class EntityDoesNotExistException extends RuntimeException
{
    public static function createFromEntityAndConditions($entityName, array $conditions)
    {
        return new self(sprintf(
            'Entity of type %s with params [%s] does not exist',
            $entityName,
            static::serializeParams($conditions)
        ));
    }

    private static function serializeParams(array $params)
    {
        $result = [];
        foreach ($params as $key => $value) {
            $result[] = sprintf('"%s" => "%s"', $key, $value);
        }

        return implode(', ', $result);
    }
}
