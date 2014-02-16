<?php

abstract class Strass_Views_PagesRenderer_Historique extends Wtk_Pages_Renderer
{
  function __construct($view)
  {
    $this->view = $view;
    parent::__construct($view->url(array('unite' => $view->unite->slug,
					 'annee' => '%i')),
			true, null);
  }

  function intituleChef($chef, $unite) {
    $type = $unite->findParentTypesUnite();
    if ($chef == '##INCONNU##')
      return '//chef inconnu//';
    else if (is_null($chef)) {
      switch($type->slug) {
      case 'troupe':
	return 'foulard noir';
	break;
      default:
	return '//chef inconnu//';
	break;
      }
    }
    else {
      switch($type->slug) {
      case 'groupe':
      case 'aines':
	return $chef->nom;
	break;
      case 'meute':
      case 'ronde':
	return $chef->voirNom() ? $chef->prenom : $chef->getName();
	break;
      default:
	return $chef->prenom;
	break;
      }
    }
  }

  function titreChef($chef, $intitule) {
    if (is_object($chef)) {
      $lien = $this->view->lienIndividu($chef, $intitule);
      if ($lien->metas)
	$lien->metas->title = $chef->getFullName();
      return $lien;
    }
    else
      return new Wtk_Inline($intitule);
  }

  function renderLinks($pages, $model)
  {
    $count = $model->pagesCount();
    if ($count == 1)
      return;

    $ss = $pages->addSection('historique', "Historique");
    $ss->addFlags('pagelinks');
    $sss = null;
    $pre = null;

    foreach($model->data as $annee => $chef) {
      $intitule = $this->intituleChef($chef, $model->unite);
      if (!$sss || $pre != $chef) {
	$titre = new Wtk_Container("L'annee ", $this->titreChef($chef, $intitule));
	$sss = $ss->addSection(null, $intitule ? $titre : null);
	$l = $sss->addList();
	$pre = $chef;
      }
      else {
	$titre = new Wtk_Container("Les annees ", $this->titreChef($chef, $intitule));
	$sss->setTitle($titre);
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