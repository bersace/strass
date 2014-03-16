<?php

class Wtk_Inline extends Wtk_Text
{
  function __construct($text = '')
  {
    parent::__construct($text);

    // Désactivation de certaine fonctionnalité peu sécurisé ou utiles dans
    // le cadre d'un wiki uniquement.
    $disable = array('blockquote',
		     'center',	'code',		'deflist',	'heading',	'horiz',
		     'html',	'list',		'newline',	'paragraph',	'raw',
		     'table');

    foreach($disable as $rule)
      $this->tw->disableRule($rule);
  }

  function __toString()
  {
	  return $this->text;
  }
}