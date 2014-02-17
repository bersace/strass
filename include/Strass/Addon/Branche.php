<?php

class Strass_Addon_Branche extends Strass_Addon_Liens
{
  function __construct()
  {
    parent::__construct('branche', 'Vous êtes ici :');
  }

  protected function lien($metas = null, array $urlOptions = array(), array $acl = array(), $reset = false)
  {
    if (!$metas) {
      $page = Zend_Registry::get('page');
      $metas = $page->metas->get('DC.Title.alternative');
    }

    if (!$reset) {
      $r = Zend_Controller_Front::getInstance()->getRequest();
      $urlOptions = array_merge(array('controller' => $r->getControllerName(),
				      'action' => $r->getActionName()),
				$r->getUserParams(),
				$urlOptions);
    }

    return parent::lien($metas, $urlOptions, $acl, $reset);
  }

  function initView ($view)
  {
    parent::initView($view);
    $view->parent = $view->document->header;
  }
}
