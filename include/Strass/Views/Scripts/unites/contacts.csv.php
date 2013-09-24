<?php
foreach($this->apps as $app)
	$this->csvIndividu($app->findParentIndividus());

foreach($this->sousunites as $unite) {
	foreach($this->sousapps[$unite->id] as $app) 
		$this->csvIndividu($app->findParentIndividus());
}
