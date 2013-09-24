<?php

$s = $this->content->addSection("maitrise", "Maîtrise");

$model = null;
foreach($this->apps as $apps) {
	if (!$model)
		$model = $this->appsTableModel($apps);
	else
		$this->appsTableModel($apps, $model);
}
$t = $s->addChild($this->tableEffectifs($model, true, 'maitrise'));
