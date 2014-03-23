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
    if ($chef === '##INCONNU##')
      return new Wtk_Emphasis('chef inconnu');
    else if ($chef === '##SANSCHEF##') {
      switch($type->slug) {
      case 'troupe':
	return 'foulard noir';
	break;
      default:
	return new Wtk_Emphasis('sans chef');
	break;
      }
    }
    if (is_string($chef))
      return new Wtk_Emphasis($chef);
    else {
      switch($type->slug) {
      case 'groupe':
      case 'aines':
	return $chef->capitalizedLastname();
	break;
      case 'meute':
      case 'ronde':
	if ($this->view->assert(null, $chef, 'voir-nom'))
	  return $chef->prenom;
	else
	  return $chef->getName();
	break;
      default:
	return $chef->homonymes > 1 ? $chef->getFullName(true, false, true) : $chef->prenom;
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
      return $intitule;
  }

  function renderLinks($pages, $model)
  {
    $ss = $pages->addSection('historique', "Historique");
    $ss->addFlags('pagelinks');
    $sss = null;
    $pre = null;

    foreach($model->data as $annee => $chef) {
      $intitule = $this->intituleChef($chef, $model->unite);
      if (!$sss || $pre !== $chef) {
	$titre = new Wtk_Container("L'année ", $this->titreChef($chef, $intitule));
	$sss = $ss->addSection(null, $intitule ? $titre : null);
	$l = $sss->addList();
	$pre = $chef;
      }
      else {
	$titre = new Wtk_Container("Les années ", $this->titreChef($chef, $intitule));
	$sss->setTitle($titre);
      }
      $etiq = $annee."-".($annee+1);
      $i = $l->addItem($this->view->lien(str_replace('%i', $annee, $this->href),
					 $etiq));
      if ($annee == $model->current) {
	$i->addFlags('selectionnee');
      }
    }
  }
}
