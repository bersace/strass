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
	$image->addFlags('vignette');
      }
      else {
	$photo = $photos->findPhotoAleatoireUnite($unite);
	if (!$photo)
	  $photo = $photos->findPhotoAleatoireUnite($unite->findParentUnites());
	if ($photo)
	  $image = new Wtk_Image($photo->getCheminVignette(),
				 $photo->titre.' '.$this->_view->page->metas->get('DC.Subject'),
				 $photo->titre);
	else
	  $image = new Wtk_Paragraph("Pas d'image !");
      }

      $url = $this->_view->url(array('unite' => $unite->id));
      $link = new Wtk_Link($url, $label,
			   new Wtk_Container($image, new Wtk_Paragraph($label)));
      $link->addFlags($unite->type);
      $item = $list->addItem($link);
      $item->addFlags($unite->type, 'vignette');

      // insérer les sous unités ouverte à la suite
      $this->renderUnites($list, $unite->getSousUnites(false, $annee), $annee);
    }
  }


  function render($annee, $data, $s)
  {
    $v = $this->_view;
    $v->document->addStyleComponents('vignette');
    extract($data);

    // Présentation
    $ss = $s->addSection('presentation');
    if ($texte)
      $ss->addText($texte);

    // Section les unités
    if (!$unite->parent) {
      $unites = [$unite];
    }
    else {
      $unites = $unite->getSousUnites(false, $annee);
    }

    if ($unites) {
      $ss = $s->addSection('unites', 'Les '.$unite->getSousTypeName(true));
      $l = $ss->addList();
      $this->renderUnites($l, $unites, $annee);
    }
  }
}


$this->document->addStyleComponents('accueil', 'effectifs');
$s = $this->content->addSection("accueil", wtk_ucfirst($this->unite->getName())." ".$this->unite->extra);
$s->addPages(null, $this->model,
	     new Strass_Views_PagesRenderer_Unites_Accueil($this,
							   $this->unite->getAnneesOuverte(), $this->annee));
