<?php

class Strass_Views_PagesRenderer_Unites_Contacts extends Strass_Views_PagesRenderer_Historique {
  function render($annee, $data, $s) {
    extract($data);

    if (!$apps->count() && !count($sousunites))
      return;


    $ss = $s->addSection("effectifs")->addFlags('effectifs');
    if (!$unite->isTerminale()) {
      $sss = $ss->addSection('maitrise', "Maîtrise");
    }
    else {
      $sss = $ss;
    }
  
    if ($apps->count()) {
      $t = $sss->addChild($this->view->tableEffectifs($this->view->appsTableModel($apps),
						       $this->view->profils, 'contacts'));
      $t->addFlags($unite->type);
    }

    foreach($sousunites as $unite) {
      $apps = $sousapps[$unite->id];
      if ($apps instanceof Iterator) {
	$sss = $ss->addSection($unite->id,
			       $this->view->lienUnite($unite, null, null, false));
	$t = $sss->addChild($this->view->tableEffectifs($this->view->appsTableModel($apps),
							 $this->view->profils, 'contacts'));
	$t->addFlags($unite->type);
	$t->show_header = false;
      }
    }
  }
}

$s = $this->content->addFlags($this->unite->type);
$s->addPages(null, $this->model,
	     new Strass_Views_PagesRenderer_Unites_Contacts($this));
