<?php

require_once 'Zend/Auth/Result.php';

class Strass_Auth_Adapter_Sudo implements Zend_Auth_Adapter_Interface
{
    public $target;

    function authenticate()
    {
        /* Injecte une identité arbitraire dans l'authentification. */
        return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->target->getIdentity());
    }
}
