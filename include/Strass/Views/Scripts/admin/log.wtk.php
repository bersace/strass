<?php

class Strass_Pages_Renderer_Log extends Wtk_Pages_Renderer
{
  private $view;

  function __construct($view)
  {
    $this->view = $view;
    parent::__construct($view->url(array('page' => '%i')), true,
			array('previous' => "Précédents",
			      'next'	 => "Suivants"));
  }

  function renderContainer()
  {
    $model = $m = new Wtk_Table_Model('date', 'level', 'logger', 'label', 'url',
				      'prenom-nom', 'fiche');
    $table = $t = new Wtk_Table($model, true, 'level');
    $t->addFlags('logs');

    $t->addNewColumn("Niveau", new Wtk_Table_CellRenderer_Text('text', 'level'));
    $t->addNewColumn("Date", new Wtk_Table_CellRenderer_Text('text', 'date'));
    $t->addNewColumn("Émetteur", new Wtk_Table_CellRenderer_Text('text', 'logger'));
    $t->addNewColumn("Utilistateur", new Wtk_Table_CellRenderer_Link('href', 'fiche',
								     'label', 'prenom-nom'));
    $t->addNewColumn("Message", new Wtk_Table_CellRenderer_Link('href', 'url',
							     'label', 'label'));

    return $table;
  }

  function render($id, $event, $table)
  {
    $m = $table->getModel();
    $u = $event->findParentUsers();
    if ($u) {
      $i = $u->findParentIndividus();
      $pn = $i->getFullname(false, false);
      $fiche = $this->view->url(array('controlleur' => 'individus', 'action' => 'fiche',
				      'individu' => $i->slug), true);
    }
    else {
      $pn = "Visiteur";
      $fiche = null;
    }
    $m->append($event->date, strtolower($event->level), $event->logger,
	       wtk_first_words($event->message, 56), $event->url,
	       $pn, $fiche);
  }
}

$this->document->addPages(null, $this->events,
			  new Strass_Pages_Renderer_Log($this));
