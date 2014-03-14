<?php

class Strass_Views_PagesRenderer_Unites_Contacts extends Strass_Views_PagesRenderer_Historique {
  function render($annee, $data, $s) {
    extract($data);

    if ($apps->count()) {
      $type = $unite->findParentTypesUnite();
      $t = $s->addChild($this->view->tableEffectifs($unite,
						    $this->view->appsTableModel($apps, null, $unite),
						    $this->view->fiches,
						    array('adelec', 'portable', 'fixe')));
    }
    else {
      $s->addParagraph()->addFlags('empty')
	->addInline("Aucun inscrit en ".$annee." !");
    }
  }
}

$s = $this->document->addFlags($this->unite->findParentTypesUnite()->slug);
$renderer = new Strass_Views_PagesRenderer_Unites_Contacts($this);
$s->addPages(null, $this->model, $renderer);
