<?php

class Strass_Pages_Renderer_Individus extends Wtk_Pages_Renderer
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
    $model = $m = new Wtk_Table_Model('prenom-nom', 'fiche', 'adelec', 'app', 'url-app', 'statut',
				      'url-supprimer', 'url-admin', 'flags');
    $table = $t = new Wtk_Table($model, true, 'flags');
    $t->addFlags('individus');

    $t->addNewColumn("Utilisateur", new Wtk_Table_CellRenderer_Link('href', 'fiche',
								    'label', 'prenom-nom'));
    $r = new Wtk_Table_CellRenderer_Link('href', 'adelec',
					 'label', 'adelec');
    $r->setUrlFormat('mailto:%s');
    $t->addNewColumn("Adélec", $r);
    $t->addNewColumn("Inscription", new Wtk_Table_CellRenderer_Link('href', 'url-app',
								    'label', 'app'));
    $t->addNewColumn("Statut", new Wtk_Table_CellRenderer_Text('text', 'statut'));

    $t->addNewColumn(null, new Wtk_Table_CellRenderer_Link('href', 'url-admin',
							   'label', 'Administrer',
							   'flags', array('adminlink')),
		     'adminlinks');
    $t->addNewColumn(null, new Wtk_Table_CellRenderer_Link('href', 'url-supprimer',
							   'label', 'Supprimer',
							   'flags', array('adminlink', 'critical')),
		     'adminlinks');

    return $table;
  }

  function render($id, $i, $table)
  {
    $m = $table->getModel();
    $pn = $i->getFullname(false, false);
    $fiche = $this->view->url(array('controller' => 'individus', 'action' => 'fiche',
				    'individu' => $i->slug), true);
    $urlsupp = $this->view->url(array('controller' => 'individus', 'action' => 'supprimer',
				      'individu' => $i->slug), true);
    $urladm = $this->view->url(array('controller' => 'individus', 'action' => 'admin',
				     'individu' => $i->slug), true);
    $u = $i->findUser();

    $flags = array();
    if ($u->admin) {
      $statut = 'Administrateur';
      array_push($flags, 'admin');
    }
    else if ($u->isMember()) {
      $statut = 'Membre';
      array_push($flags, 'membre');
    }
    else {
      $statut = 'Visiteur';
    }

    if ($app = $i->findAppartenances()->current()) {
      $urlapp = $this->view->url(array('controller' => 'unites', 'action' => 'index',
				       'unite' => $app->findParentUnites()->slug,
				       'annee' => $app->getAnnee()), true);
      $appdesc = $app->getShortDescription();
    }
    else {
      $appdesc = 'Non inscrit';
      $urlapp = $this->view->url(array('controller' => 'individus', 'action' => 'inscrire',
				       'individu' => $i->slug), true);
    }

    $m->append($pn, $fiche, $i->adelec, $appdesc, $urlapp, $statut, $urlsupp, $urladm, $flags);
  }
}

$this->document->addPages(null, $this->individus,
			  new Strass_Pages_Renderer_Individus($this));
