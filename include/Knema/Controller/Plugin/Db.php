<?php

class Knema_Controller_Plugin_Db extends Zend_Controller_Plugin_Abstract
{
	public function routeStartup()
	{
		$options = array();
		$config = new Knema_Config_Php('knema/db');
		$db = Zend_Db::factory($config->adapter,
				       $config->config);

		Zend_Db_Table_Abstract::setDefaultAdapter($db);
		$cache = Zend_Cache::factory('Core', 'File',
					     array('automatic_serialization' => true),
					     array('cache_dir' => 'cache'));
		Zend_Registry::set('cache', $cache);
		Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
		Zend_Registry::set('db', $db);
	}
}
