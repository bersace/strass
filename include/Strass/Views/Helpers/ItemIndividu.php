<?php

class Strass_View_Helper_ItemIndividu
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function itemIndividu($individu)
  {
    $lien = $this->view->lienIndividu($individu);
    $acl = Zend_Registry::get('acl');
    $self = Zend_Registry::get('user');
    $tf = $individu->findParentFamilles()->telephone;
    $telephone = $acl->isAllowed($self, $individu, 'voir') ?
      $tf ? $tf : $individu->telephone : NULL;
    return $telephone ? new Wtk_Container($lien, new Wtk_Inline(" //".$telephone."//")) : $lien;
  }
}
