<?php

class Wtk_Inline extends Wtk_Text
{
  protected static	$tw;

  function __construct($text = '')
  {
    parent::__construct($text);

    if (!self::$tw) {
      self::$tw = clone parent::$tw;

      // Désactivation de certaine fonctionnalité peu sécurisé ou utiles dans
      // le cadre d'un wiki uniquement.
      $disable = array('blockquote',
		       'center',	'code',		'deflist',	'heading',	'horiz',
		       'html',		'list',		'newline',	'paragraph',	'raw',
		       'table');

      foreach($disable as $rule) {
	self::$tw->disableRule($rule);
      }
    }
    $this->tw = self::$tw;
  }
  function __toString()
  {
	  return $this->text;
  }
}