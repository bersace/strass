<?php

class Scout_Table_CellRenderer_Role extends Wtk_Table_CellRenderer
{
	protected	$properties = array('app'	=> null);
	protected	$view;

	function setView($view)
	{
		$this->view = $view;
	}

	function element ($data)
	{
		extract($data);
		if ($app) {
			return $this->view->lienUnite($app->findParentUnites(),
						      null,
						      array('annee' => strftime('%Y', strtotime($app->debut))));
		}
		else {
			return new Wtk_RawText('');
		}
	}
}


class Scout_Page_RendererIndividu extends Wtk_Pages_Renderer
{
	protected	$view;

	function __construct($view)
	{
		parent::__construct($view->url(array('page' => '%i')),
				    true,
				    array('previous'	=> "Précédents",
					  'next'	=> "Suivants"));
		$this->view = $view;
	}

	function renderContainer()
	{
		$m = new Wtk_Table_Model('id', 'prenom-nom', 'adelec', 'telephone', 'fiche', 'app', 'role', 'annee');
		$t = new Wtk_Table($m);
		$t->addColumn(new Wtk_Table_Column("Nom",
						   new Wtk_Table_CellRenderer_Link('href', 'fiche',
										   'label', 'prenom-nom')));
		$r = new Wtk_Table_CellRenderer_Link('href', 'adelec',
						     'label', 'adelec');
		$r->setUrlFormat('mailto:%s');
		$t->addColumn(new Wtk_Table_Column("Adélec", $r));
		$t->addColumn(new Wtk_Table_Column("Téléphone",
						   new Wtk_Table_CellRenderer_Text('text', 'telephone')));
		$t->addColumn(new Wtk_Table_Column("Poste",
						   new Wtk_Table_CellRenderer_Text('text', 'role')));
		$r = new Scout_Table_CellRenderer_Role('app', 'app', 'annee', 'annee');
		$r->setView($this->view);
		$t->addColumn(new Wtk_Table_Column("Unité", $r));

		return $t;
	}

	function render($id, $i, $table)
	{
		$m = $table->getModel();
		$telephone = $i->fixe;
		$telephone = $telephone ? $telephone : $i->portable;
		$app = $i->findAppartenances(null, 'fin DESC')->current();
		$m->append($i->id,
			   $i->getFullName(true, false),
			   $i->adelec,
			   $telephone,
			   $this->view->url(array('controller' => 'individus',
						  'action' => 'fiche',
						  'individu' => $i->id,
						  'page' => null)),
			   $app,
			   $app ? $app->findParentRoles()->getAccronyme() : null,
			   $app ? strftime('%Y', strtotime($app->fin)) : '');
	}
}

$s = $this->document->addSection('nonenregistres', "Les non enregistrés");
$s->addParagraph("Voici la liste des personnes inscrit dans cette unité, mais ne s'étant pas enregistré sur le site.");
$s->addChild(new Wtk_Pages(null, $this->individus,
			   new Scout_Page_RendererIndividu($this, $this->fiches)));
