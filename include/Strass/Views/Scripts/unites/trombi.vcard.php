<?php

foreach($this->apps as $app)
	$this->carteIndividu($app->findParentIndividus());
			     