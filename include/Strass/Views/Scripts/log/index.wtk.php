<?php
class Strass_Pages_Renderer_Log extends Wtk_Pages_Renderer
{
	private $view;
	function __construct($view)
	{
		$this->view = $view;
		parent::__construct($view->url(array('page' => '%i')), true,
				    array('previous'	=> "Précédents",
					  'next'	=> "Suivants"));
	}

	function renderContainer()
	{
		$model = $m = new Wtk_Table_Model('utilisateur', 'date', 'url', 'titre', 'detail');
		$table = $t = new Wtk_Table($model);

		$t->addColumn(new Wtk_Table_Column("Date", new Wtk_Table_CellRenderer_Text('text', 'date')));

		$r = new Wtk_Table_CellRenderer_Link('href', 'utilisateur',
						     'label', 'utilisateur');
		$r->setUrlFormat($this->view->url(array('controller' => 'membres',
							'action' => 'profil',
							'membre' => '%s'),
						  true));
		$t->addColumn(new Wtk_Table_Column("Utilistateur", $r));
		$t->addColumn(new Wtk_Table_Column("Détail", new Wtk_Table_CellRenderer_Text('text', 'detail')));

		$r = new Wtk_Table_CellRenderer_Link('href', 'url',
						     'label', 'titre');
		$t->addColumn(new Wtk_Table_Column("Page", $r));

		return $table;
	}

	function render($id, $log, $table)
	{
		$m = $table->getModel();
		$m->append($log['username'], $log['date'], $log['url'], $log['titre'], $log['detail']);
	}
}

$this->document->addPages(null, $this->logs,
			 new Strass_Pages_Renderer_Log($this));