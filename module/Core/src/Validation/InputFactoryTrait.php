<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Validation;

use Zend\Filter\StringTrim;
use Zend\Filter\StripTags;
use Zend\InputFilter\Input;

trait InputFactoryTrait
{
    private function createInput($name, $required = true): Input
    {
        $input = new Input($name);
        $input->setRequired($required)
              ->getFilterChain()->attach(new StripTags())
                                ->attach(new StringTrim());
        return $input;
    }
}
