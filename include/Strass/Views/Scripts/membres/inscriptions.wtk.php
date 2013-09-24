<?php
class Scout_Page_RendererInscriptions extends Wtk_Pages_Renderer
{
	protected	$view;

	function __construct($view)
	{
		parent::__construct($view->url(array('page' => '%i')),
				    true,
				    array('previous'	=> "Précédents",
					  'next'		=> "Suivants"));
		$this->view = $view;
	}

	function renderContainer()
	{
		$m = new Wtk_Table_Model('prenom-nom', 'username', 'adelec', 'fixe', 'portable');
		$t = new Wtk_Table($m);

		// prénom-nom
		$r = new Wtk_Table_CellRenderer_Link('href', 'username',
						     'label', 'prenom-nom');
		$r->setUrlFormat($this->view->url(array('action' => 'valider',
							'id' => '%s',
							'page' => null)));
		$t->addColumn(new Wtk_Table_Column("Nom", $r));

		// fixe
		$t->addColumn(new Wtk_Table_Column("Fixe",
						   new Wtk_Table_CellRenderer_Text('text', 'fixe')));

		// portable
		$t->addColumn(new Wtk_Table_Column("Portable",
						   new Wtk_Table_CellRenderer_Text('text', 'portable')));

		// adélec
		$r = new Wtk_Table_CellRenderer_Link('href', 'adelec',
						     'label', 'adelec');
		$r->setUrlFormat('mailto:%s');
		$t->addColumn(new Wtk_Table_Column("Adélec", $r));


		return $t;
	}

	function render($id, $i, $table)
	{
		$m = $table->getModel();
		$m->append($i->prenom.' '.$i->nom,
			   $i->username,
			   $i->adelec,
			   $i->fixe,
			   $i->portable);
	}
}

$s = $this->content->addSection('inscriptions', "Inscriptions en attente");
$s->addChild(new Wtk_Pages(null, $this->inscriptions,
			   new Scout_Page_RendererInscriptions($this)));
