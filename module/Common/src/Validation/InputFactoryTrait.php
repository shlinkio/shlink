<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Validation;

use Zend\Filter;
use Zend\InputFilter\Input;
use Zend\Validator;

trait InputFactoryTrait
{
    private function createInput($name, $required = true): Input
    {
        $input = new Input($name);
        $input->setRequired($required)
              ->getFilterChain()->attach(new Filter\StripTags())
                                ->attach(new Filter\StringTrim());
        return $input;
    }

    private function createBooleanInput(string $name, bool $required = true): Input
    {
        $input = $this->createInput($name, $required);
        $input->getFilterChain()->attach(new Filter\Boolean());
        $input->getValidatorChain()->attach(new Validator\NotEmpty(['type' => [
            Validator\NotEmpty::OBJECT,
            Validator\NotEmpty::SPACE,
            Validator\NotEmpty::NULL,
            Validator\NotEmpty::EMPTY_ARRAY,
            Validator\NotEmpty::STRING,
        ]]));

        return $input;
    }
}
