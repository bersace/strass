<?php

class Wtk_Form_Model_Instance_EMail extends Wtk_Form_Model_Instance_String
{
    static $pattern = (
        '/^[[:alnum:]\._+-]{3,}@[[:alnum:]\._-]{3,}(\.[[:alnum:]]{2,6})*$/');

    function retrieve($value)
    {
        if ($this->readonly)
            return true;

        $value = strtolower($value);
        $this->set($value);

        if (!(bool) preg_match(self::$pattern, $value)) {
            throw new Wtk_Form_Model_Exception(
                "Adresse électronique invalide", $this);
        }

        return true;
    }
}
