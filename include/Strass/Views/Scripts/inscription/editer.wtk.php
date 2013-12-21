<?php

class Scout_Pages_Renderer_EditerIndividu extends Wtk_Pages_Renderer_Form
{
	protected $view;

	function __construct($view, $model)
	{
		parent::__construct($model);
		$this->view = $view;
	}

	function renderActuel($group, $f)
	{
		$f->addDate('actuel/fin', '%e-%m-%Y');
		try {
			$f->addCheck('actuel/promouvoir');
		}
		catch(Exception $e) {
		}
	}

	function renderUnite($group, $f)
	{
		$f->addSelect('unite/unite', true);
	}

	function renderAppartenance($group, $f)
	{
		$f->addSelect('appartenance/role', true);
		if ($this->model->get('appartenance/debut')) {
			$f->addDate('appartenance/debut', '%e/%m/%Y');
		}
	}
}


$s = $this->document->addSection('editer', new Wtk_Container(new Wtk_RawText("Modifier la fiche de "),
							    $this->lienIndividu($this->individu)));

$s->addChild(new Wtk_Pages(null,
			   $this->model,
			   new Scout_Pages_Renderer_EditerIndividu($this, $this->model->getFormModel()),
			   false));