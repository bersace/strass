<?php

$this->document->addStyleComponents('effectifs');

$s = $this->content->addFlags($this->unite->type);

if ($this->apps->count() || count($this->sousunites)) {
	$ss = $s->addSection("progression", "Progressions personnelles")->addFlags('effectifs');
	if (!$this->unite->isTerminale()) {
		$sss = $ss->addSection('maitrise', "Maîtrise");
	}
	else {
		$sss = $ss;
	}
  
	if ($this->apps->count()) {
		$t = $sss->addChild($this->tableEffectifs($this->appsTableModel($this->apps), true, 'progressions'));
		$t->addFlags($this->unite->type);
	}

	foreach($this->sousunites as $unite) {
		$apps = $this->sousapps[$unite->id];
		if ($apps instanceof Iterator) {
			$sss = $ss->addSection($unite->id,
					       $this->lienUnite($unite, null, null, false));
			$t = $sss->addChild($this->tableEffectifs($this->appsTableModel($apps), true, 'progressions'));
			$t->addFlags($unite->type);
		}
	}
 }
