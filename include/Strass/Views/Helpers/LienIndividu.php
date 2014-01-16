<?php

class Strass_View_Helper_LienIndividu
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function lienIndividu($individu,
			       $label = null,
			       $action = 'fiche',
			       $controller = 'individus')
  {
    if (!$individu) {
      return null;
    }

    $acl = Zend_Registry::get('acl');
    if ($acl->isAllowed(Zend_Registry::get('user'),
			$individu, $action)) {
      $lien =  $this->view->lien(array('action' => $action,
				       'individu' => $individu->slug,
				       'controller' => $controller),
				 $label ? $label : wtk_nbsp($individu->getFullName()),
				 true);

      $etape = $individu->findParentEtapes();
      if ($etape)
	$lien->addFlags($etape->slug);

      return $lien;
    }
    else
      return new Wtk_RawText($label ? $label : $individu->getName());
  }
}