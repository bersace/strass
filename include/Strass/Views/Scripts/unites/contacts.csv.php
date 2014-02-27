<?php
extract($this->model->fetch($this->model->current));
foreach($apps as $app)
	$this->csvIndividu($app->findParentIndividus());
