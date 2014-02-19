<?php

class Strass_Page_RendererInscriptions extends Wtk_Pages_Renderer
{
  protected	$view;

  function __construct($view)
  {
    parent::__construct($view->url(array('page' => '%i')),
			true,
			array('previous'	=> "Précédentes",
			      'next'		=> "Suivantes"));
    $this->view = $view;
  }

  function renderContainer()
  {
    $m = new Wtk_Table_Model('date', 'prenom-nom', 'adelec');
    $t = new Wtk_Table($m);

    $t->addNewColumn("Date", new Wtk_Table_CellRenderer_Text('text', 'date'));
    $r = new Wtk_Table_CellRenderer_Link('href', 'adelec',
					 'label', 'prenom-nom');
    $r->setUrlFormat($this->view->url(array('action' => 'valider',
					    'adelec' => '%s',
					    'page' => null)));
    $t->addNewColumn("Nom", $r);

    $r = new Wtk_Table_CellRenderer_Link('href', 'adelec',
					 'label', 'adelec');
    $r->setUrlFormat('mailto:%s');
    $t->addNewColumn("Adélec", $r);


    return $t;
  }

  function renderEmpty($cont)
  {
    $cont->addParagraph('Aucune inscription à valider')->addFlags('empty');
  }

  function render($id, $i, $table)
  {
    $m = $table->getModel();
    $m->append(strftime('%d/%m/%Y %H:%M', strtotime($i->date)),
	       $i->getFullname(),
	       $i->adelec);
  }
}

$this->document->addPages(null, $this->inscriptions, new Strass_Page_RendererInscriptions($this));
