<?php

class Strass_Addon_Branche extends Strass_Addon_Liens
{
  function __construct()
  {
    parent::__construct('branche', 'Vous êtes ici :');
  }

  protected function lien($metas = null, array $urlOptions = array(), array $acl = array(), $reset = false)
  {
    if ($acl && $acl[0] == null)
      $acl[0] = Zend_Registry::get('user');

    if (!$metas) {
      $page = Zend_Registry::get('page');
      $metas = $page->metas->get('DC.Title');
    }

    if (!$metas)
      return false;

    if ($acl && count($acl) < 3 && isset($urlOptions['action']))
      array_push($acl, $urlOptions['action']);

    if (!is_array($metas))
      $metas = array('label' => $metas);

    if (!$reset) {
      $r = Zend_Controller_Front::getInstance()->getRequest();
      $urlOptions = array_merge(array('controller' => $r->getControllerName(),
				      'action' => $r->getActionName()),
				$r->getParams(),
				$urlOptions);
    }

    return array('metas' => $metas,
		 'urlOptions' => $urlOptions,
		 'acl' => $acl,
		 'reset' => $reset);
  }

  function viewScript()
  {
    $c = explode('_', __CLASS__);
    return strtolower($c[2]);
  }
}
