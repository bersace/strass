<?php

$s = $this->content->addFlags($this->unite->type);

if ($this->apps->count() || count($this->sousunites)) {
	$ss = $s->addSection("effectifs")->addFlags('effectifs');
	if (!$this->unite->isTerminale()) {
		$sss = $ss->addSection('maitrise', "Maîtrise");
	}
	else {
		$sss = $ss;
	}
  
	if ($this->apps->count()) {
		$t = $sss->addChild($this->tableEffectifs($this->appsTableModel($this->apps),
							  $this->profils, 'contacts'));
		$t->addFlags($this->unite->type);
	}

	foreach($this->sousunites as $unite) {
		$apps = $this->sousapps[$unite->id];
		if ($apps instanceof Iterator) {
			$sss = $ss->addSection($unite->id,
					       $this->lienUnite($unite, null, null, false));
			$t = $sss->addChild($this->tableEffectifs($this->appsTableModel($apps),
								  $this->profils, 'contacts'));
			$t->addFlags($unite->type);
			$t->show_header = false;
		}
	}
 }

// Affichages des « années », largement inspiré de 27paris.net.
if (count($this->annees) && $this->format == 'xhtml') {
	$ss = $s->addSection('historique', "Historique");
	$pre = -1;
	foreach($this->annees as $annee => $chef) {
		// gérer les cas où on ne connaît pas le chefs.
		switch($this->unite->type) {
		case 'groupe':
			$intitule = $chef ? "« ".$chef->nom." »" : null;
			break;
		case 'troupe':
			$intitule = $chef ? "« ".$chef->prenom." »" : 'foulard noir';
			break;
		case 'meute':
		case 'ronde':
			if ($chef)
				$intitule = $chef->voirNom() ? "« ".$chef->prenom." »" : null;
			break;
		default:
			$intitule = $chef ? "« ".$chef->prenom." »" : null;
			break;
		}

		if ($pre == -1 || $pre != $intitule) {
			$sss = $ss->addSection(null, $intitule ? "L'année ".$intitule : null);
			$l = $sss->addChild(new Wtk_List());
			$pre = $intitule;
		}
		else {
			$sss->setTitle($intitule ? "Les années ".$intitule : null);
		}
		$etiq = $annee."-".($annee+1);
		$i = $l->addItem($this->lien(array('annee' => $annee),
					     $etiq));
		if ($annee == $this->annee) {
			$i->addFlags('selectionnee');
		}
	}
 }
