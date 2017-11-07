<?php

class Strass_View_Helper_VignetteIndividu
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function vignetteIndividu($individu,
				   $label = null,
				   $urlOptions = array())
  {
    if (!$individu)
      return;

    $urlOptions = array_merge(array('controller'	=> 'individus',
				    'action'		=> 'fiche',
				    'individu'		=> $individu->slug),
			      $urlOptions);

    $label = $label ? $label : $individu->getFullname();
    $item = new Wtk_Container;
    $section = $item->addSection()
      ->addFlags('wrapper');
    if ($this->view->assert(null, $individu, 'voir-avatar') && $src = $individu->getCheminImage())
      $section->addImage($src, $individu->getFullname(), $individu->getFullname());
    else
      $section->addParagraph("Pas de photo")->addFlags('empty', 'image');
    $item->addParagraph($label)->addFlags('label');
    if ($individu->slug)
        $url = $this->view->url($urlOptions, true, true);
    else
        $url = null;

    $link = new Wtk_Link($url, $label, $item);
    $link->addFlags('vignette', 'individu', 'avatar');

    return $link;
  }
}
