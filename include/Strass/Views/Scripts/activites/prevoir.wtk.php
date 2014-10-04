<?php

class Strass_Views_PagesRenderer_Prevoir extends Strass_Views_PagesRenderer_Historique
{
  function render($annee, $data, $s)
  {
    extract($data);

    $i = $model->getInstance('unites');
    $pour = (count($i) == 1 ? " pour ".current(current($i)) :"");
    $this->view->document->setTitle("Prévoir une nouvelle activité".$pour);

    $s->addChild($this->view->Calendrier($calendrier, $annee));

    $f = $s->addForm($model);
    $g = $f->addForm_Fieldset('Informations générales');
    $i = $model->getInstance('unites');
    if (count($i) > 1)
      $g->addSelect('unites', true);
    else
      $g->addHidden('unites');
    $c = $g->addDate('debut', 'le %d-%m-%Y à %H heures %M');
    $c = $g->addDate('fin', 'le %d-%m-%Y à %H heures %M');

    $g->addParagraph()->addFlags('info')
      ->addChild("Laisser ce champ vide et l'intitulé sera généré, sinon le remplir sans date. ".
		 "(Ex: Rentrée, JN, RNR, Vezelay, etc.)");
    $g->addEntry('intitule', 32);

    $f->addCheck('prevoir');

    $b = $f->addForm_ButtonBox();
    $b->addForm_Submit($model->getSubmission('ajouter'));

    return $s;
  }
}

$this->document->addPages(null, $this->model, new Strass_Views_PagesRenderer_Prevoir($this));
