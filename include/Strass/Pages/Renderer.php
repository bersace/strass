<?php

abstract class Strass_Pages_Renderer extends Wtk_Pages_Renderer
{
  static $masculin = array('previous' => 'Précédents',
			   'next' => 'Suivants');
  static $feminin = array('previous' => 'Précédentes',
			  'next' => 'Suivantes');

  public $view;

  function __construct($view, $href, $intermediate = true, $labels = null)
  {
    /* Strass est sexiste */
    $labels = $labels ? $labels : self::$masculin;
    parent::__construct($href, $intermediate, $labels);
    $this->view = $view;
  }
}
