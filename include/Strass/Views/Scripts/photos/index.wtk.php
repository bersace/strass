<?php

class Scout_Pages_Renderer_Albums extends Wtk_Pages_Renderer
{
	protected $view;
    
	function __construct($view)
	{
		parent::__construct($view->url(array('page' => '%i')),
				    true, array('previous' => 'Précédents',
						'next' => 'Suivants'));
		$this->view = $view;
	}
    
	function renderContainer()
	{
		return new Wtk_List();
	}
    
	function render($id, $act, $l)
	{
		$v = $this->view->vignettePhoto($act->getPhotoAleatoire(),
						wtk_ucfirst($act->getIntitule(false)),
						array('action'		=> 'consulter',
						      'controller'	=> 'photos',
						      'activite'	=> $act->id,
						      'photo'		=> null),
						true);
		$l->addItem($v)->addFlags('vignette');
	}
}

$this->document->addStyleComponents('vignette');

$s = $this->content->addSection('albums', "Photos d'activités ".$this->annee);
$s->addPages(null, $this->activites, new Scout_Pages_Renderer_Albums($this));
$s = $this->content->addSection('historique', 'Historique');
$l = $s->addList();
foreach($this->annees as $annee) {
	if ($annee['annee'])
		$l->addItem($this->Lien(array('annee' => $annee['annee']), $annee));
}

