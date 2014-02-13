<?php

class Strass_Controller_Plugin_Page extends Zend_Controller_Plugin_Abstract
{
  function preDispatch()
  {
    $config = new Strass_Config_Php('strass');
    $metas = $config->site->metas;
    $this->page = new Strass_Page(new Wtk_Metas(array('DC.Title'		=> $metas->title,
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
