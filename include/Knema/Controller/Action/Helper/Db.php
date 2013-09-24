<?php

class Knema_Controller_Action_Helper_Db extends Zend_Controller_Action_Helper_Abstract
{
  protected $db;

  public function init()
  {
    $options = array();
    $config = new Knema_Config_Php('knema/site');
    $this->db = Zend_Db::factory('Pdo_SQLite',
				 array ('dbname'	=> 'data/db/'.$config->id.'.sqlite',
					'options'	=> $options));

    Zend_Db_Table_Abstract::setDefaultAdapter($this->db);
    $cache = Zend_Cache::factory('Core', 'File', array('automatic_serialization' => true),
                                                 array('cache_dir' => 'cache'));
    Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
  }

  public function direct()
  {
    return $this->db;
  }
}
