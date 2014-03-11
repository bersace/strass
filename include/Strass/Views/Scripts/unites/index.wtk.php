<?php

class Strass_Views_PagesRenderer_Unites_Accueil extends Strass_Views_PagesRenderer_Historique
{
  function render($annee, $data, $s)
  {
    extract($data);

    // Présentation
    $src = $unite->getImage();
    if ($texte || $src) {
      $ss = $s->addSection('presentation');

      if ($src) {
	$ss->addImage($src, "Photos d'unité", $unite->getFullname());
      }

      if ($texte) {
	$ss->addText($texte);
      }
    }

    foreach ($this->view->blocs as $bloc) {
      $method = 'bloc'.$bloc;
      if (!method_exists($this, $method))
	throw new Exception("Impossible de générer le bloc ".$bloc);
      call_user_func_array(array($this, $method), array($annee, $data, $s));
    }
  }

  function blocUnites($annee, $data, $s)
  {
    extract($data);

    $this->view->document->addStyleComponents('vignette');
    $ss = $s->addSection('unites', 'Les '.$unite->getSousTypeName(true));
    $ss->addFlags('bloc');
    if ($sousunites->count()) {
      $l = $ss->addList();
      $l->addFlags('vignettes', 'unites');
      foreach ($sousunites as $unite) {
	$item = $l->addItem($this->view->vignetteUnite($unite, $annee));
	$item->addFlags('vignette');
      }
    }
    else {
      $ss->addParagraph()->addFlags('empty')
	->addInline("Pas d'unités actives en ${annee} !");
    }
  }

  function blocPhotos($annee, $data, $s)
  {
    extract($data);

    $this->view->document->addStyleComponents('vignette');
    $ss = $s->addSection('photos',
			 $this->view->lien(array('controller' => 'photos',
						 'action' => null,
						 'unite' => $unite->slug,
						 'annee' => $annee),
					   'Les photos', true));
    $ss->addFlags('bloc');
    if ($photos->count()) {
      $l = $ss->addList();
      $l->addFlags('vignettes', 'photos');
      foreach($photos as $photo) {
	$i = $l->addItem($this->view->vignettePhoto($photo));
	$i->addFlags('vignette');
      }
    }
    else {
      $ss->addParagraph()->addFlags('empty')
	->addInline("Pas de photos d'activités ".$annee." !");
    }
  }

  function blocActivites($annee, $data, $s)
  {
    extract($data);

    $this->view->document->addStyleComponents('vignette');
    $ss = $s->addSection('activites',
			 $this->view->lien(array('controller' => 'activites',
						 'action' => 'calendrier',
						 'unite' => $unite->slug,
						 'annee' => $annee),
					   'Activités marquantes', true));
    $ss->addFlags('bloc');
    if ($activites->count()) {
      $l = $ss->addList();
      $l->addFlags('vignettes', 'activites');
      foreach($activites as $activite) {
	$i = $l->addItem($this->view->vignettePhoto($activite->getPhotoAleatoire(),
						    $activite->getIntituleCourt(),
						    array('action'		=> 'consulter',
							  'controller'	=> 'photos',
							  'album'	=> $activite->slug),
						    true));
	$i->addFlags('vignette');
      }
    }
    else {
      $ss->addParagraph()->addFlags('empty')
	->addInline("Pas d'activités ".$annee." !");
    }
  }
}

$this->document->addPages(null, $this->model,
			  new Strass_Views_PagesRenderer_Unites_Accueil($this));
