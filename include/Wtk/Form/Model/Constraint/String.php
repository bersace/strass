<?php

class Wtk_Form_Model_Constraint_String extends Wtk_Form_Model_Constraint_Pattern
{

  const LOWER	= 1;
  const UPPER	= 2;
  const ALPHA	= 4;
  const ALNUM	= 8;
  const DIGIT	= 16;
  const XDIGIT	= 32;
  const PUNCT	= 64;
  const SPACE	= 128;

  protected	$flag;
  protected	$min;
  protected	$length;

  function __construct ($instance, $flag, $min = 1, $length = NULL)
  {
    $this->classes = $this->getClasses ($flag);
    $this->min = $min;
    $this->lenght = $length;
    parent::__construct ($instance, $this->buildPattern ($this->classes, $min, $length));
  }

  function getClasses ($flag)
  {
    $classes = array ();

    if ($flag & self::LOWER) {
      $classes['lower'] = 'minuscule';
    }

    if ($flag & self::UPPER) {
      $classes['upper'] = 'majuscule';
    }

    if ($flag & self::ALPHA) {
      $classes['alpha'] = 'lettre';
    }

    if ($flag & self::ALNUM) {
      $classes['alnum'] = 'lettre, chiffre';
    }

    if ($flag & self::DIGIT) {
      $classes['digit'] = 'chiffre';
    }

    if ($flag & self::XDIGIT) {
      $classes['xdigit'] = 'chiffre hexadécimal';
    }

    if ($flag & self::PUNCT) {
      $classes['punct'] = 'ponctuation';
    }

    if ($flag & self::SPACE) {
      $classes['space'] = 'espace';
    }

    return $classes;    
  } 

  protected function buildPattern ($classes, $min, $length)
  {
    return "`^[[:".implode (':][:', array_keys ($classes)).":]]{".$min.",".$length."}`";
  }

  function validate ()
  {
    try {
      parent::validate ();
    }
    catch (Wtk_Form_Model_ConstraintException $e) {
      $message = "Le champ %s";
      if ($this->min == $this->length) {
	$message.= " ne doit contenir";
	if ($this->min == 1) {
	  $message.= " qu'un seul";
	}
	else {
	  $message.= " que ".$this->min;
	}
      }
      else if (!$this->length) {
	$message.= " doit contenir au moins ".$this->min;
      }
      else {
	$message.= " doit contenir de ".$this->min." à ".$this->length;
      }

      $classes = $this->classes;
      $last = array_pop ($classes);
      $message.= " ".implode (", ", $classes)." ou ".$last.".";

      throw new Wtk_Form_Model_ConstraintException ($message, $this->instance);
    }
  }
}