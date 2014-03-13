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
    if (!$individu && !$individu->getImage())
      return;

    $urlOptions = array_merge(array('controller'	=> 'individus',
				    'action'		=> 'fiche',
				    'individu'		=> $individu->slug),
			      $urlOptions);

    $this->view->document->addStyleComponents('vignette');
    $label = $label ? $label : $individu->getFullname();
    $item = new Wtk_Container;
    $item->addSection()
      ->addFlags('wrapper')
      ->addImage($individu->getImage(), $individu->getFullname(), $individu->getFullname());
    $item->addParagraph($label)->addFlags('label');
    $link = new Wtk_Link($this->view->url($urlOptions, true, true),
			 $label, $item);
    $link->addFlags('vignette', 'individu', 'avatar');
    return $link;
  }
}