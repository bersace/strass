<?php

foreach($this->apps as $app)
	$this->carteIndividu($app->findParentIndividus());

foreach($this->sousunites as $unite) {
	foreach($this->sousapps[$unite->id] as $app) 
		$this->carteIndividu($app->findParentIndividus());
}