<?php

class Strass_Controller_Plugin_Db extends Zend_Controller_Plugin_Abstract
{
	public function routeStartup()
	{
		$options = array();
		$config = new Strass_Config_Php('strass');
		$db = Zend_Db::factory($config->db->adapter,
				       $config->db->config);

		Zend_Db_Table_Abstract::setDefaultAdapter($db);
		$cache = Zend_Cache::factory('Core', 'File',
					     array('automatic_serialization' => true),
					     array('cache_dir' => 'private/cache'));
		Zend_Registry::set('cache', $cache);
		Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
		Zend_Registry::set('db', $db);
	}
}
