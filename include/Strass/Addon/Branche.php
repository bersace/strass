<?php

class Strass_Addon_Branche extends Strass_Addon_Liens
{
    function __construct()
    {
        parent::__construct('branche', 'Vous êtes ici :');
    }
  
    function viewScript()
    {
      $c = explode('_', __CLASS__);
      return strtolower($c[2]);
    }
}