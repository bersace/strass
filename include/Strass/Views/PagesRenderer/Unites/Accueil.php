<?php

function unites_accueil_pack($view, $list, $unites)
{
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
			       $photo->titre.' '.$view->page->metas->get('DC.Subject'),
			       $photo->titre);
      else
	$image = new Wtk_Paragraph("Pas d'image !");
    }

    $url = $view->url(array('unite' => $unite->id));
    $link = new Wtk_Link($url, $lable,
			 new Wtk_Container($image, new Wtk_Paragraph($label)));
    $link->addFlags($unite->type);
    $item = $list->addItem($link);
    $item->addFlags($unite->type, 'vignette');

    // insérer les sou unités ouverte à la suite
    unites_accueil_pack($view, $list, $unite->getSousUnites(false, false));
  }
}

class Strass_Views_PagesRenderer_Unites_Accueil extends Wtk_Pages_Renderer
{
	protected $_view;

	function __construct($view)
	{
		$this->_view = $view;
		parent::__construct($view->url(array('unite' => $view->unite->id,
						     'annee' => '%i')),
				    true, null);
	}

	function render($annee, $data, $s)
	{
		$v = $this->_view;
		$v->document->addStyleComponents('vignette');
		extract($data);

		// présentation
		$ss = $s->addSection('presentation');
		if ($texte)
			$ss->addText($texte);

		// Photos des unités
		$ss = $s->addSection('unites', 'Les unités');
		$l = $ss->addList();
		unites_accueil_pack($v, $l, [$unite]);
	}
}

