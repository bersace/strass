<?php

class Strass_View_Helper_VignettePhoto
{
	protected	$view;

	public function setView($view)
	{
		$this->view = $view;
	}

	public function vignettePhoto($photo,
				      $label = null,
				      $urlOptions = array(),
				      $reset = true)
	{
		if (!$photo) {
			return;
		}

		$urlOptions = array_merge(array('controller'	=> 'photos',
						'action'		=> 'voir',
						'activite'		=> $photo->activite,
						'photo'		=> $photo->id),
					  $urlOptions);

		$this->view->document->addStyleComponents('vignette');
		$label = $label ? $label : ucfirst($photo->titre);
		$page = Zend_Registry::get('page');
		$item = new Wtk_Container(new Wtk_Image($photo->getCheminVignette(),
							$photo->titre.' '.$page->metas->get('DC.Subject'),
							$photo->titre),
					  new Wtk_Paragraph($label));
		return new Wtk_Link($this->view->url($urlOptions, null, true).'#photo',
				    $label, $item);
	}
}
