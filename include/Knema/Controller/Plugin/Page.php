<?php

class Knema_Controller_Plugin_Page extends Zend_Controller_Plugin_Abstract
{
	function preDispatch()
	{
		$config = new Knema_Config_Php('strass');
		Zend_Registry::set('site', $config->site);
		$metas = $site->metas;
		$this->page = new Knema_Page(new Wtk_Metas(array('DC.Title'		=> $metas->title,
								 'DC.Title.alternative'	=> $metas->title,
								 'DC.Subject'		=> $metas->subject,
								 'DC.Language'		=> $metas->language,
								 'DC.Creator'		=> $metas->author,
								 'DC.Date.created'	=> $metas->creation,
								 'DC.Date.available'	=> strftime('%Y-%m-%d'),
								 'organization'	=> $metas->organization,)));
    		Zend_Registry::set('page', $this->page);
	}
}