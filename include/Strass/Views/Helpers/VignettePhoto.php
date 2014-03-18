<?php

class Strass_View_Helper_VignettePhoto
{
  protected	$view;

  public function setView($view)
  {
    $this->view = $view;
  }

  public function vignettePhoto($photo, $label = null,$urlOptions = array())
  {
    if (!$photo)
      return;

    $urlOptions = array_merge(array('controller'	=> 'photos',
				    'action'		=> 'voir',
				    'photo'		=> $photo->slug),
			      $urlOptions);

    $this->view->document->addStyleComponents('vignette');
    $label = $label ? $label : wtk_ucfirst($photo->titre);
    $page = Zend_Registry::get('page');
    $item = new Wtk_Container;
    $item->addSection()
      ->addFlags('wrapper')
      ->addImage($photo->getCheminVignette(),
		 $photo->titre.' '.$page->metas->get('DC.Subject'),
		 $photo->titre);
    $item->addParagraph($label)->addFlags('label');
    $link = new Wtk_Link($this->view->url($urlOptions, true, true).'#document',
			 $label, $item);
    $link->addFlags('vignette', 'photo');
    return $link;
  }
}
