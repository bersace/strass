<?php

class Scout_Pages_Renderer_EditerHistorique extends Wtk_Pages_Renderer_Form
{
  function renderUnite($group, $f)
  {
    $f->addSelect('unite/unite', true);
  }

  function renderAppartenance($group, $f)
  {
    $f->addSelect('appartenance/role', true);
    $f->addDate('appartenance/debut', '%e/%m/%Y');
    $f->addDate('appartenance/fin', '%e/%m/%Y');
    $f->addCheck('appartenance/continuer');
  }
}


$s = $this->document->addSection('historique',
				new Wtk_Container("Compléter l'historique du scoutisme de ",
						  $this->lienIndividu($this->individu)));

$s->addPages(null,
	     $this->model,
	     new Scout_Pages_Renderer_EditerHistorique($this->model->getFormModel()),
	     false);