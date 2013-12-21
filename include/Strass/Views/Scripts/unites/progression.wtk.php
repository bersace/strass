<?php

class Strass_Pages_Renderer_ProgressionUnite extends Wtk_Pages_Renderer_Form
{
	protected $view;


	function __construct($view, $model)
	{
		parent::__construct($model);
		$this->view = $view;
	}

	function renderEtape($group, $f)
	{
		$f->addSelect('etape/etape');
		$f->addDate('etape/date', '%e-%m-%Y');
		$f->addEntry('etape/lieu', 24);
	}

	function renderIndividus($group, $f)
	{
		$f->addSelect('individus/individus');
	}
}

$s = $this->document->addSection('progression', "Enregistrer la progression");
$s->addPages(null, $this->model,
	     new Strass_Pages_Renderer_ProgressionUnite($this, $this->model->getFormModel()),
	     false);