<?php

class Strass_Views_PagesRenderer_Unites_Accueil extends Strass_Views_PagesRenderer_Historique
{
  function renderUnites($list, $unites, $annee) {
    foreach ($unites as $unite) {
      $label = $unite->getName();

      $src = $unite->getImage();
      if ($src) {
	$image = new Wtk_Image($src, "Photo d'unité", $label);
      }
      else {
	$photo = $unite->findPhotoAleatoire();
	if (!$photo)
	  $photo = $unite->findParentUnites()->findPhotoAleatoire();
	if ($photo)
	  $image = new Wtk_Image($photo->getCheminVignette(),
				 $photo->titre.' '.$this->view->page->metas->get('DC.Subject'),
				 $photo->titre);
	else {
	  $image = new Wtk_Paragraph("Pas d'image !");
	  $image->addFlags('empty', 'image');
	}
      }

      $url = $this->view->url(array('unite' => $unite->slug));
      $type = $unite->findParentTypesUnite();
      $w = new Wtk_Section;
      $w->addFlags('wrapper')->addChild($image);
      $link = new Wtk_Link($url, $label,
			   new Wtk_Container($w, new Wtk_Paragraph($label)));
      $link->addFlags($type->slug);
      $item = $list->addItem($link);
      $item->addFlags($type->slug);
      if ($src)
	$item->addFlags('unite');
      else
	$item->addFlags('vignette');
    }
  }

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

    if (!$unite->isTerminale()) {
      $this->view->document->addStyleComponents('vignette');
      $ss = $s->addSection('unites', 'Les '.$unite->getSousTypeName(true));
      if ($sousunites) {
	$l = $ss->addList();
	$l->addFlags('vignettes', 'unites');
	$this->renderUnites($l, $sousunites, $annee);
      }
      else {
	$ss->addParagraph()->addFlags('empty')
	  ->addInline("Pas d'unités actives !");
      }
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
