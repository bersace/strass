<?php

class Strass_View_Helper_CarteIndividu
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  function CarteIndividu($individu)
  {
    $v = new vCard;
    $acl = Zend_Registry::get('acl');
    $ind = Zend_Registry::get('user');
    if ($acl->isAllowed($ind, $individu, 'fiche')) {
      $v->setName($individu->nom, $individu->prenom);
      if ($individu->naissance)
	$v->setBirthday($individu->naissance);

      $t0 = explode("\n", $individu->adresse);
      $t0 = array_pad($t0, 3, '');
      list($adresse, $ville, $pays) = $t0;
      if (preg_match("`(\d{5}) (.*)`", $ville, $res))
	$v->setAddress("", "", $adresse, $res[2], "", $res[1], $pays);

      $v->setPhoneNumber($individu->fixe, "HOME");
      $v->setPhoneNumber($individu->portable, "CELL");

      if ($photo = $individu->getCheminImage())
	$v->setPhoto('jpeg', file_get_contents($photo));

      $v->setEmail($individu->adelec);
      $v->setURL($this->view->urlIndividu($individu, 'fiche', 'individus', true, true));
    }
    else {
      $v->setName($individu->getName());
      $v->setBirthday(substr($individu->naissance, 0, 4));
    }

    array_push($this->view->vcards, $v);
  }
}