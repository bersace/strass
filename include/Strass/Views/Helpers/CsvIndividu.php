<?php

class Strass_View_Helper_CsvIndividu
{
  protected	$view;

  static $columns = array('Name'		=> 'prenom-nom',
			  'E-mail Address'	=> 'adelec',
			  'Notes'		=> 'notes',
			  'E-mail 2 Address'	=> null,
			  'E-mail 3 Address'	=> null,
			  'Mobile Phone'	=> 'portable',
			  'Pager'		=> null,
			  'Company'		=> null,
			  'Job Title'		=> null, // situation ?
			  'Home Phone'		=> 'fixe',
			  'Home Phone 2'	=> null,
			  'Home Fax'		=> null,
			  'Home Address'	=> 'adresse',
			  'Business Phone'	=> null,
			  'Business Phone 2'	=> null,
			  'Business Fax'	=> null,
			  'Business Address'	=> null,
			  'Other Phone'		=> null,
			  'Other Fax'		=> null,
			  'Other Address'	=> null);

  public function setView($view)
  {
    $this->view = $view;
  }

  function header()
  {
    echo implode(",", array_keys(self::$columns))."\r\n";
  }

  function CsvIndividu($individu)
  {
    static $first = false;
    if (!$first) {
      $this->header();
      $first = true;
    }

    $acl = Zend_Registry::get('acl');
    $moi = Zend_Registry::get('user');
    $vals = array();
    foreach(self::$columns as $col => $attr) {
      switch($attr) {
      case 'prenom-nom':
	$val = $individu->getFullname(true, false);
	break;
      case null:
	$val = null;
	break;
      default:
	$val = preg_replace("`\r?\n`", " – ", trim($individu->$attr));
	break;
      }
      $vals[] = $val ? '"'.str_replace('"', '""', $val).'"' : $val;
    }
    echo implode(",",$vals)."\r\n";
  }
}