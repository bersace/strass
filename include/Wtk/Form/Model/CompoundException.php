<?php

class Wtk_Form_Model_CompoundException extends Exception
{

    function __construct($errors)
    {
        $this->errors = $errors;
    }
}
