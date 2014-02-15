<?php

require_once 'Individus.php';

class Logs extends Zend_Db_Table_Abstract implements Zend_Acl_Resource_Interface
{
  const LEVEL_INFO = 'INFO';
  const LEVEL_WARNING = 'WARNING';
  const LEVEL_ERROR = 'ERROR';
  const LEVEL_CRITICAL = 'CRITICAL';

  protected $_name = 'log';
  protected $_referenceMap = array('User' => array('columns' => 'user',
						   'refTableClass' => 'Users',
						   'refColumns'	=> 'id',
						   'onUpdate' => self::CASCADE,
						   'onDelete' => self::CASCADE),
				   );

  function __construct()
  {
    parent::__construct();

    $acl = Zend_Registry::get('acl');
    if (!$acl->has($this))
      $acl->add(new Zend_Acl_Resource($this->getResourceId()));
  }

  function getResourceId()
  { return 'log'; }
}
