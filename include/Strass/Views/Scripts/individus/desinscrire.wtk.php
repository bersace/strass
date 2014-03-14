<?php

$this->document->addStyleComponents('unites');

$s = $this->document->addSection('inscription');
$s->addChild($this->vignetteIndividu($this->individu));
$m = new Wtk_Table_Model('unite_slug', 'unite_type', 'unite_nom', 'unite_lien',
			 'role', 'accr', 'acl',
			 'debut', 'fin');

$role = $this->app->findParentRoles();
$unite = $this->app->findParentUnites();
$url_unite = $this->url(array('controller' => 'unites', 'action' => 'contacts',
			      'unite' => $unite->slug, 'annee' => $this->app->getAnnee()), true);
$fin = $this->app->fin ? strftime('au %x', strtotime($this->app->fin)) : "à aujourd'hui";
$m->append($unite->slug,
	   $unite->findParentTypesUnite()->slug,
	   $unite->getFullName(),
	   $url_unite,
	   array($role->slug, wtk_strtoid($this->app->titre)),
	   $this->app->getAccronyme(),
	   $role->acl_role,
	   strftime('du %x', strtotime($this->app->debut)), $fin
	   );

$t = $s->addTable($m, true, array('acl', 'role'));
$config = Zend_Registry::get('config');
$t->addFlags('effectifs', $config->system->mouvement, 'appartenances');
$t->addNewColumn('Poste', new Wtk_Table_CellRenderer_Text('text', 'accr'));
$t->addNewColumn('Unité', new Wtk_Table_CellRenderer_Link('href', 'unite_lien',
							  'label', 'unite_nom'), 'unite');
$t->addNewColumn('Début', new Wtk_Table_CellRenderer_Text('text', 'debut'));
$t->addNewColumn('Fin', new Wtk_Table_CellRenderer_Text('text', 'fin'));

$f = $this->document->addForm($this->model);
$f->addCheck('confirmer');
$f->addForm_ButtonBox()->addForm_Submit($this->model->getSubmission('continuer'));
