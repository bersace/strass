<?php

class Strass_View_Helper_VignetteUnite
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function vignetteUnite($unite, $annee = null, $label = null, $urlOptions = array())
  {
    if (!$unite)
      return;

    $this->view->document->addStyleComponents('vignette');
    $label = $label ? $label : $unite->getName();

    $src = $unite->getImage();
    if ($src) {
      $image = new Wtk_Image($src, "Photo d'unité", $label);
    }
    else {
      $photo = $unite->findPhotoAleatoire($annee);
      if (!$photo)
	$photo = $unite->findParentUnites()->findPhotoAleatoire($annee);
      if ($photo)
	$image = new Wtk_Image($photo->getCheminVignette(),
			       $photo->titre, $unite->getFullname());
      else {
	$image = new Wtk_Paragraph("Pas d'image !");
	$image->addFlags('empty', 'image');
      }
    }

    $urlOptions = array_merge(array('controller'	=> 'unites',
				    'action'		=> 'index',
				    'unite'		=> $unite->slug),
			      $urlOptions);
    $type = $unite->findParentTypesUnite();
    $w = new Wtk_Section;
    $w->addFlags('wrapper')->addChild($image);
    $plabel = new Wtk_Paragraph($label);
    $plabel->addFlags('label');

    $link = new Wtk_Link($this->view->url($urlOptions), $label,
			 new Wtk_Container($w, $plabel));
    $link->addFlags('vignette', $type->slug);
    if ($src)
	$link->addFlags('unite');
    else
	$link->addFlags('photo');

    return $link;
  }
}
