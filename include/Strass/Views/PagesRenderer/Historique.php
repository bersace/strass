<?php

abstract class Strass_Views_PagesRenderer_Historique extends Wtk_Pages_Renderer
{
  function __construct($view)
  {
    $this->view = $view;
    parent::__construct($view->url(array('unite' => $view->unite->id,
					 'annee' => '%i')),
			true, null);
  }


  function renderLinks($pages, $model)
  {
    $count = $model->pagesCount();
    if ($count == 1)
      return;

    $ss = $pages->addSection('historique', "Historique");
    $ss->addFlags('pagelinks');
    $pre = null;
    foreach($model->data as $annee => $chef) {
      switch($model->unite->type) {
      case 'groupe':
      case 'aines':
	$intitule = $chef ? $chef->nom : null;
	break;
      case 'troupe':
	$intitule = $chef ? $chef->prenom : 'foulard noir';
	break;
      case 'meute':
      case 'ronde':
	if ($chef)
	  $intitule = $chef->voirNom() ? $chef->prenom : null;
	break;
      default:
	$intitule = $chef ? $chef->prenom : null;
	break;
      }

      if ($pre == null || $pre != $chef) {
	$sss = $ss->addSection(null, $intitule ? "L'année ".$intitule : null);
	$l = $sss->addList();
	$pre = $chef;
      }
      else {
	$sss->setTitle($intitule ? "Les années ".$intitule : null);
      }
      $etiq = $annee."-".($annee+1);
      $i = $l->addItem($this->view->lien(array('annee' => $annee),
				   $etiq));
      if ($annee == $model->current) {
	$i->addFlags('selectionnee');
      }
    }
  }
}