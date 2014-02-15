<?php

if ($this->unites->count()) {
  $t = $this->document->addTable($this->unites, true, 'flags');
  $r = new Wtk_Table_CellRenderer_Link('href', 'accueil', 'label', 'nom');
  $t->addNewColumn('Nom', new Wtk_Table_CellRenderer_TreeNode($r));
  $t->addNewColumn('Statut', new Wtk_Table_CellRenderer_Text('text', 'statut'));
  $t->addNewColumn('Chef', new Wtk_Table_CellRenderer_Link('href', 'fiche-chef', 'label', 'chef'));
  $t->addNewColumn('Inscrits', new Wtk_Table_CellRenderer_Text('text', 'inscrits'));
  $t->addNewColumn(null, new Wtk_Table_CellRenderer_Link('href', 'url-supprimer',
							 'label', 'Supprimer',
							 'flags', array('adminlink', 'critical')),
		   'adminlinks');
}
else {
  $this->document->addParagraph('Aucune unité')->addFlags('empty');
}
