<?php

namespace ApiInputBundle\Controller;

use ApiInputBundle\Input\Input;
use ApiInputBundle\Input\Type;

/**
 * Controller
 */
trait InputTrait
{
    /**
     * Create input
     *
     * @param Type   $type    type
     * @param object $data    data
     * @param array  $options options
     *
     * @return Input
     */
    public function createInput(Type $type, $data, array $options = [])
    {
        $input = new Input($type, $data, $options);

        return $input;
    }
}
