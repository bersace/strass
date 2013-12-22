<?php

abstract class Wtk_Pages_Renderer
{
	protected	$labels = array('previous'	=> 'Previous',
					'next'		=> 'Next');
	protected	$intermediate;
	protected	$href;
	protected	$model;


	// Si $labels est null, les liens précédent/suivant ne seront
	// pas affichés.
	function __construct($href,
			     $intermediate = true,
			     $labels = array())
	{
		if (is_null($labels) || count($labels)) 
			$this->labels = $labels;
		$this->href		= $href;
		$this->intermediate	= $intermediate;
	}

	function renderContainer()
	{
		return new Wtk_Container();
	}

	abstract function render($id, $data, $container);

	function renderEmpty($container)
	{
	  return $container->addRawText("Pas de contenu");
	}

	function renderLinks($pages, $model)
	{
		if ($model->pagesCount() == 1)
			return;

		$this->model = $model;

		$l = $pages->addChild(new Wtk_List());
		$l->addFlags('pages', 'links');

		if ($this->labels != null && $pid = $model->getPrevId()) {
			$i = $l->addItem($this->renderLink($pid, $this->labels['previous']));
			if ($i) {$i->addFlags('pages', 'previous'); }
		}

		if ($this->intermediate && $model->pagesCount()>1) {
			foreach($model->getPagesIds() as $id) {
				$i = $l->addItem($this->renderLink($id));
				if ($i) {
					$i->addFlags('pages');
					if ($id == $model->getCurrentPageId()) {
						$i->addFlags('current');
					}
				}
			}
		}

		if ($this->labels && $sid = $model->getNextId()) {
			$i = $l->addItem($this->renderLink($sid, $this->labels['next']));
			if ($i) {$i->addFlags('pages', 'next'); }
		}
	}

	protected function renderLink($id, $label = null)
	{
		if (!is_null($id)) {
			return new Wtk_Link(str_replace("%i", $id, $this->href),
					    $label ? $label : $this->getLabel($id));
		}
		return NULL;
	}

	protected function getLabel($id)
	{
		return strval($id);
	}

}
