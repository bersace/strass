<?php

class IndexController extends Strass_Controller_Action
{
  public function indexAction()
  {
    $unite = $this->_helper->Unite();
    if ($unite) {
      $this->redirectSimple('accueil', 'unites', null, array('unite' => $unite->id));
    } else {
      Orror::kill("Pas d'unités");
    }
  }
}
