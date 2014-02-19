<?php

class Strass_Pages_Renderer_Membres extends Wtk_Pages_Renderer
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
    $model = $m = new Wtk_Table_Model('prenom-nom', 'fiche', 'username', 'last_login', 'admin', 'flags');
    $table = $t = new Wtk_Table($model, true, 'flags');
    $t->addFlags('membres');

    $t->addNewColumn("Utilisateur", new Wtk_Table_CellRenderer_Link('href', 'fiche',
								    'label', 'prenom-nom'));
    $t->addNewColumn("Identifiant", new Wtk_Table_CellRenderer_Text('text', 'username'));
    $t->addNewColumn("Dernière connexion", new Wtk_Table_CellRenderer_Text('text', 'last_login'));
    $t->addNewColumn("Administrateur", new Wtk_Table_CellRenderer_Text('text', 'admin'));

    return $table;
  }

  function render($id, $u, $table)
  {
    $m = $table->getModel();
    $i = $u->findParentIndividus();
    $pn = $i->getFullname(false, false);
    $fiche = $this->view->url(array('controller' => 'individus', 'action' => 'fiche',
				    'individu' => $i->slug), true);

    $flags = array();
    if ($u->admin)
      array_push($flags, 'admin');

    $m->append($pn, $fiche,
	       $u->username, $u->last_login,
	       $u->admin ? 'Oui' : 'Non',
	       $flags);
  }
}

$this->document->addPages(null, $this->membres,
			  new Strass_Pages_Renderer_Membres($this));
