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
	protected	$profils;

	function __construct($view, $profils)
	{
		parent::__construct($view->url(array('page' => '%i')),
				    true,
				    array('previous'	=> "Précédents",
					  'next'		=> "Suivants"));
		$this->view = $view;
		$this->profils = $profils;
	}

	function renderContainer()
	{
		$m = new Wtk_Table_Model('id', 'prenom-nom', 'adelec', 'telephone', 'profil', 'app', 'role', 'annee');
		$t = new Wtk_Table($m);
		if ($this->profils) {
			$t->addColumn(new Wtk_Table_Column("Nom",
							   new Wtk_Table_CellRenderer_Link('href', 'profil',
											   'label', 'prenom-nom')));
			$r = new Wtk_Table_CellRenderer_Link('href', 'adelec',
							     'label', 'adelec');
			$r->setUrlFormat('mailto:%s');
			$t->addColumn(new Wtk_Table_Column("Adélec", $r));
			$t->addColumn(new Wtk_Table_Column("Téléphone",
							   new Wtk_Table_CellRenderer_Text('text', 'telephone')));
		}
		else {
			$t->addColumn(new Wtk_Table_Column("Nom",
							   new Wtk_Table_CellRenderer_Text('text', 'prenom-nom')));
		}
		$t->addColumn(new Wtk_Table_Column("Dernier poste",
						   new Wtk_Table_CellRenderer_Text('text', 'role')));
		$r = new Scout_Table_CellRenderer_Role('app', 'app', 'annee', 'annee');
		$r->setView($this->view);
		$t->addColumn(new Wtk_Table_Column("Unité", $r));
		$t->addColumn(new Wtk_Table_Column("Année",
						   new Wtk_Table_CellRenderer_Text('text', 'annee')));
    
		return $t;
	}

	function render($id, $i, $table)
	{
		$m = $table->getModel();
		$telephone = $i->fixe;
		$telephone = $telephone ? $telephone : $i->portable;
		$s = $i->getTable()->select()
			->order('fin DESC');
		$app = $i->findAppartenances($s)->current();
		$m->append($i->id,
			   $i->getFullName(true, false),
			   $i->adelec,
			   $telephone,
			   $this->view->url(array('controller' => 'individus',
						  'action' => 'voir',
						  'individu' => $i->id,
						  'page' => null)),
			   $app,
			   $app ? $app->findParentRoles()->getAccronyme() : null,
			   $app ? strftime('%Y', strtotime($app->fin)) : '');
	}
}

$s = $this->content->addSection('anciens', "Les anciens d".($this->unite ? "e ".$this->unite->getFullName() : "u groupe"));
$s->addPages(null, $this->individus,
	     new Scout_Page_RendererIndividu($this, $this->profils));
