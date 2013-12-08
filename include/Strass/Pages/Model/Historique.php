<?php

abstract class Strass_Pages_Model_Historique extends Wtk_Pages_Model_Assoc
{
  public $unite;

  function __construct(Unite $unite, $annee)
  {
    $this->unite = $unite;
    parent::__construct($unite->getAnneesOuverte(), $annee);
  }
}
