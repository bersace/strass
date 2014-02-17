<?php

$s = $this->document->addSection('indicateurs');
$t = $s->addTable($this->indicateurs, false, 'level');
$t->addNewColumn(null, new Wtk_Table_CellRenderer_Link('href', 'url',
						       'label', 'label'));
$t->addNewColumn(null, new Wtk_Table_CellRenderer_Text('text', 'compteur'));

$s = $this->document->addSection('log',
				 new Wtk_Link($this->url(array('controller' => 'admin',
							       'action' => 'log')),
					      'Journal système'));
if ($this->log->count()) {
  $t = $s->addTable($this->log, false, 'level')->addFlags('logs');
  $t->addNewColumn(null, new Wtk_Table_CellRenderer_Text('text', 'logger'));
  $t->addNewColumn(null, new Wtk_Table_CellRenderer_Link('href', 'fiche',
							 'label', 'prenom-nom'));
  $t->addNewColumn(null, new Wtk_Table_CellRenderer_Link('href', 'url',
							 'label', 'label'));
}
else {
  $s->addParagraph("Aucun évênement")->addFlags('empty');
}

$s = $this->document->addSection('membres',
				 new Wtk_Link($this->url(array('controller' => 'admin',
							       'action' => 'membres')),
					      'Dernières connexions'));
$t = $s->addTable($this->connexions, false);
$t->addNewColumn(null, new Wtk_Table_CellRenderer_Text('text', 'date'));
$t->addNewColumn(null, new Wtk_Table_CellRenderer_Link('href', 'fiche',
						       'label', 'prenom-nom'));
