<?php

class Strass_Views_PagesRenderer_Unites_Accueil extends Strass_Views_PagesRenderer_Historique
{
  function renderUnites($list, $unites, $annee) {
    $photos = new Photos;
    foreach ($unites as $unite) {
      $label = wtk_ucfirst($unite->getName());

      $src = $unite->getImage();
      if ($src) {
	$image = new Wtk_Image($src, "Photo d'unité", $label);
      }
      else {
	$photo = $photos->findPhotoAleatoireUnite($unite);
	if (!$photo)
	  $photo = $photos->findPhotoAleatoireUnite($unite->findParentUnites());
	if ($photo)
	  $image = new Wtk_Image($photo->getCheminVignette(),
				 $photo->titre.' '.$this->view->page->metas->get('DC.Subject'),
				 $photo->titre);
	else {
	  $image = new Wtk_Paragraph("Pas d'image !");
	  $image->addFlags('image');
	}
      }

      $url = $this->view->url(array('unite' => $unite->slug));
      $link = new Wtk_Link($url, $label,
			   new Wtk_Container($image, new Wtk_Paragraph($label)));
      $link->addFlags($unite->type);
      $item = $list->addItem($link);
      $item->addFlags($unite->type);
      if ($src)
	$item->addFlags('unite');
      else
	$item->addFlags('vignette');

      // insérer les sous unités ouverte à la suite
      //$this->renderUnites($list, $unite->getSousUnites(false, $annee), $annee);
    }
  }


  function render($annee, $data, $s)
  {
    extract($data);

    // Présentation
    $ss = $s->addSection('presentation');

    $src = $unite->getImage();
    if ($src) {
      $ss->addImage($src, "Photos d'unité", wtk_ucfirst($unite->getFullname()));
    }
    else {
      $ss->addParagraph()->addFlags('image', 'empty')
	->addInline("Pas d'image !");
    }

    if ($texte)
      $ss->addText($texte);
    else {
      $ss->addParagraph()->addFlags('text', 'empty')
	->addInline("Pas de présentation !");
    }

    // Section les unités
    if ($sousunites) {
      $this->view->document->addStyleComponents('vignette');
      $ss = $s->addSection('unites', 'Les '.$unite->getSousTypeName(true));
      $l = $ss->addList();
      $l->addFlags('vignettes');
      $this->renderUnites($l, $sousunites, $annee);
    }
  }
}

$this->document->addPages(null, $this->model,
			  new Strass_Views_PagesRenderer_Unites_Accueil($this));
