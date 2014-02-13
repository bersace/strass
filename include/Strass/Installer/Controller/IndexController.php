<?php

class IndexController extends Strass_Installer_Controller_Action
{
  static $mouvements = array('suf' => 'Scouts unitaires de France',
			     'fse' => "Association guides et scouts d'Europe",
			     );

  function indexAction()
  {
    $m = new Wtk_Form_Model('installation');

    $g = $m->addGroup('site', "Le site" );
    $i = $g->addEnum('mouvement', "Mouvement", null, self::$mouvements);
    $m->addConstraintRequired($i);

    $g = $m->addGroup('admin', "Votre compte" );
    $i = $g->addString('prenom', "Votre prénom");
    $m->addConstraintRequired($i);

    $i = $g->addString('nom', "Votre nom");
    $m->addConstraintRequired($i);

    $i = $g->addString('adelec', "Adélec");
    $m->addConstraintRequired($i);

    $i = $i0 = $g->addString('motdepasse', "Mot de passe");
    $m->addConstraintRequired($i);

    $i = $i1 = $g->addString('confirmation', "Confirmation");
    $m->addConstraintEqual($i1, $i0);

    $this->view->model = $pm = new Wtk_Pages_Model_Form($m);

    if ($pm->validate()) {
      Orror::kill($m->get());
    }
  }
}
