<?php

$s = $this->content;

if ($this->activites->count()) {
	$ss = $s->addSection('calendrier');
	$tam = new Wtk_Table_Model('id', 'type', 'lieu', 'date', 'intitule');

	foreach($this->activites as $a) {
		$tam->append($a->id, wtk_ucfirst($a->getTypeName()), $a->lieu,
			     wtk_ucfirst($a->getDate(false, true)),
			     wtk_ucfirst($a->getIntitule(false)));
	}
	$t = $ss->addTable($tam);

	$t->addColumn(new Wtk_Table_Column("Date",
					   new Wtk_Table_CellRenderer_Text('text', 'date')));
                                       
	$c = new Wtk_Table_CellRenderer_Link('href', 'id',
					     'label', 'intitule');
	$t->addColumn(new Wtk_Table_Column("Activité", $c));
	$url = $this->url(array('action' => $this->future ? 'consulter' : 'rapport',
				'activite' => '%s'));
	$c->setUrlFormat(urldecode($url));
                           
	if($this->future)
		$ss->addText("**La présence de chacun est primordiale** pour le bon déroulement ".
			     "des activités et pour la progression de tous.");
 }
 else {
	 $s->addText("Aucune activités prévues en ".$this->annee);
 }

if ($this->annees) {
	$ss = $s->addSection('historique', "Historique");
	$l = $ss->addList();
	foreach ($this->annees as $annee)
		{
			$l->addItem($this->lien(array('annee' => $annee['annee']),
						$annee['annee']));
		}
 }
