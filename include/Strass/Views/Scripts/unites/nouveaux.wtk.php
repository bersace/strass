<?php

class Scout_Page_RendererIndividu extends Wtk_Pages_Renderer
{
	protected	$view;
	protected	$fiches;

	function __construct($view, $fiches)
	{
		parent::__construct($view->url(array('page' => '%i')),
				    true,
				    array('previous'	=> "Précédents",
					  'next'		=> "Suivants"));
		$this->view = $view;
		$this->fiches = $fiches;
	}

	function renderContainer()
	{
		$m = new Wtk_Table_Model('id', 'prenom-nom', 'adelec', 'telephone', 'fiche');
		$t = new Wtk_Table($m);
		if ($this->fiches) {
			$t->addColumn(new Wtk_Table_Column("Nom",
							   new Wtk_Table_CellRenderer_Link('href', 'fiche',
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

		return $t;
	}

	function render($id, $i, $table)
	{
		$m = $table->getModel();
		$telephone = $i->fixe;
		$telephone = $telephone ? $telephone : $i->portable;
		$m->append($i->id,
			   $i->getFullName(true, false),
			   $i->adelec,
			   $telephone,
			   $this->view->url(array('controller' => 'individus',
						  'action' => 'fiche',
						  'individu' => $i->id,
						  'page' => null)));
	}
}

$s = $this->document->addSection('nouveaux', "Les nouveaux du groupe");
$s->addParagraph("Voici la liste des individus inscrit dans aucune unités.");
$s->addChild(new Wtk_Pages(null, $this->individus,
			   new Scout_Page_RendererIndividu($this, $this->fiches)));
