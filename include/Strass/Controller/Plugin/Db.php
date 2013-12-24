<?php

class Strass_Controller_Plugin_Db extends Zend_Controller_Plugin_Abstract
{
	public function routeStartup()
	{
	  Strass_Db::setup();
	}
}
