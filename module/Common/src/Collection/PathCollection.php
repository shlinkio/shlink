<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Collection;

use function array_key_exists;
use function array_shift;
use function is_array;

final class PathCollection
{
    /** @var array */
    private $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function pathExists(array $path): bool
    {
        return $this->checkPathExists($path, $this->array);
    }

    private function checkPathExists(array $path, array $array): bool
    {
        // As soon as a step is not found, the path does not exist
        $step = array_shift($path);
        if (! array_key_exists($step, $array)) {
            return false;
        }

        // Once the path is empty, we have found all the parts in the path
        if (empty($path)) {
            return true;
        }

        // If current value is not an array, then we have not found the path
        $newArray = $array[$step];
        if (! is_array($newArray)) {
            return false;
        }

        return $this->checkPathExists($path, $newArray);
    }

    /**
     * @return mixed
     */
    public function getValueInPath(array $path)
    {
        $array = $this->array;

        do {
            $step = array_shift($path);
            if (! is_array($array) || ! array_key_exists($step, $array)) {
                return null;
            }

            $array = $array[$step];
        } while (! empty($path));

        return $array;
    }
}
